<?php
require "../inc/check_session.inc.php";
require "../inc/db.inc.php";

// Get post ID from URL
$post_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Redirect if no valid post ID
if ($post_id <= 0) {
    header("Location: home_loggedin.php");
    exit();
}

// Process notification if there's a notification ID in the URL
if (isset($_GET['notification']) && is_numeric($_GET['notification'])) {
    $notification_id = intval($_GET['notification']);
    
    // Check if the notifications table exists
    $table_check = $conn->query("SHOW TABLES LIKE 'user_notifications'");
    if ($table_check->num_rows > 0) {
        // Mark notification as read
        $mark_stmt = $conn->prepare("
            UPDATE user_notifications 
            SET is_read = 1 
            WHERE notification_id = ? AND user_id = ?
        ");
        $mark_stmt->bind_param("ii", $notification_id, $_SESSION['user_id']);
        $mark_stmt->execute();
        $mark_stmt->close();
    }
}

// Create bookmarks table if it doesn't exist
$bookmark_check = $conn->query("SHOW TABLES LIKE 'bookmarks'");
$bookmarks_table_exists = ($bookmark_check->num_rows > 0);

if (!$bookmarks_table_exists) {
    $create_table_sql = "
        CREATE TABLE bookmarks (
            bookmark_id INT UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id INT UNSIGNED NOT NULL,
            post_id INT UNSIGNED NOT NULL,
            bookmarked_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (bookmark_id),
            UNIQUE KEY user_post (user_id, post_id),
            CONSTRAINT fk_bookmarks_user 
                FOREIGN KEY (user_id) REFERENCES user_info (member_id) ON DELETE CASCADE,
            CONSTRAINT fk_bookmarks_post 
                FOREIGN KEY (post_id) REFERENCES post_info (post_id) ON DELETE CASCADE
        ) ENGINE = InnoDB DEFAULT CHARACTER SET = utf8 COLLATE = utf8_bin
    ";
    
    $conn->query($create_table_sql);
    $bookmarks_table_exists = true;
}

// Handle bookmark/unbookmark action
$bookmarkMessage = "";
if (isset($_POST['bookmark_action'])) {
    $current_user_id = $_SESSION['user_id'];
    
    if ($_POST['bookmark_action'] == 'bookmark') {
        // Check if already bookmarked
        $check_stmt = $conn->prepare("SELECT bookmark_id FROM bookmarks WHERE user_id = ? AND post_id = ?");
        $check_stmt->bind_param("ii", $current_user_id, $post_id);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        
        if ($check_result->num_rows == 0) {
            // Not bookmarked yet, so add bookmark
            $bookmark_stmt = $conn->prepare("INSERT INTO bookmarks (user_id, post_id) VALUES (?, ?)");
            $bookmark_stmt->bind_param("ii", $current_user_id, $post_id);
            
            if ($bookmark_stmt->execute()) {
                $bookmarkMessage = "Post added to your reading list.";
            } else {
                $bookmarkMessage = "Error bookmarking post: " . $conn->error;
            }
            $bookmark_stmt->close();
        }
        $check_stmt->close();
    } elseif ($_POST['bookmark_action'] == 'unbookmark') {
        // Remove bookmark
        $unbookmark_stmt = $conn->prepare("DELETE FROM bookmarks WHERE user_id = ? AND post_id = ?");
        $unbookmark_stmt->bind_param("ii", $current_user_id, $post_id);
        
        if ($unbookmark_stmt->execute()) {
            $bookmarkMessage = "Post removed from your reading list.";
        } else {
            $bookmarkMessage = "Error removing bookmark: " . $conn->error;
        }
        $unbookmark_stmt->close();
    }
}

// Handle follow/unfollow action
$followMessage = "";
if (isset($_POST['follow_action']) && isset($_POST['author_id'])) {
    $author_id = intval($_POST['author_id']);
    $current_user_id = $_SESSION['user_id'];
    
    // Don't allow users to follow themselves
    if ($author_id != $current_user_id) {
        if ($_POST['follow_action'] == 'follow') {
            // Check if already following
            $check_stmt = $conn->prepare("SELECT follow_id FROM user_follows WHERE follower_id = ? AND following_id = ?");
            $check_stmt->bind_param("ii", $current_user_id, $author_id);
            $check_stmt->execute();
            $check_result = $check_stmt->get_result();
            
            if ($check_result->num_rows == 0) {
                // Not following yet, so add follow relationship
                $follow_stmt = $conn->prepare("INSERT INTO user_follows (follower_id, following_id) VALUES (?, ?)");
                $follow_stmt->bind_param("ii", $current_user_id, $author_id);
                
                if ($follow_stmt->execute()) {
                    $followMessage = "You are now following this author.";
                } else {
                    $followMessage = "Error following author: " . $conn->error;
                }
                $follow_stmt->close();
            }
            $check_stmt->close();
        } elseif ($_POST['follow_action'] == 'unfollow') {
            // Remove follow relationship
            $unfollow_stmt = $conn->prepare("DELETE FROM user_follows WHERE follower_id = ? AND following_id = ?");
            $unfollow_stmt->bind_param("ii", $current_user_id, $author_id);
            
            if ($unfollow_stmt->execute()) {
                $followMessage = "You have unfollowed this author.";
            } else {
                $followMessage = "Error unfollowing author: " . $conn->error;
            }
            $unfollow_stmt->close();
        }
    }
}

// Fetch post details with author information
$stmt = $conn->prepare("
    SELECT 
        p.*, 
        u.username, 
        u.fname, 
        u.lname,
        u.member_id as author_id,
        CHAR_LENGTH(p.content) / 1000 as read_time
    FROM post_info p 
    JOIN user_info u ON p.owner_id = u.member_id 
    WHERE p.post_id = ?
");
$stmt->bind_param("i", $post_id);
$stmt->execute();
$post = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Redirect if post not found
if (!$post) {
    header("Location: home_loggedin.php");
    exit();
}

// Check if current user is following the author
$isFollowing = false;
if ($post['owner_id'] != $_SESSION['user_id']) {
    $follow_check = $conn->prepare("SELECT follow_id FROM user_follows WHERE follower_id = ? AND following_id = ?");
    $follow_check->bind_param("ii", $_SESSION['user_id'], $post['owner_id']);
    $follow_check->execute();
    $isFollowing = ($follow_check->get_result()->num_rows > 0);
    $follow_check->close();
}

// Check if post is bookmarked
$isBookmarked = false;
if ($bookmarks_table_exists) {
    $bookmark_check = $conn->prepare("SELECT bookmark_id FROM bookmarks WHERE user_id = ? AND post_id = ?");
    $bookmark_check->bind_param("ii", $_SESSION['user_id'], $post_id);
    $bookmark_check->execute();
    $isBookmarked = ($bookmark_check->get_result()->num_rows > 0);
    $bookmark_check->close();
}

// Format the creation date
$created_date = new DateTime($post['created_at']);
$formatted_date = $created_date->format('F j, Y');

// Calculate read time - minimum 1 minute, assume average reading speed of 200-250 words per minute
$read_time = max(1, ceil($post['read_time']));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title><?= htmlspecialchars($post['title']) ?></title>
    <?php include "../inc/head.inc.php"; ?>
    <style>
        .post-container {
            max-width: 800px;
            margin: 30px auto;
            padding: 0;
        }
        .post-header {
            padding: 20px;
            border-bottom: 1px solid #e9ecef;
        }
        .post-title {
            font-size: 2.5rem;
            margin-bottom: 1rem;
        }
        .post-meta {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            margin-bottom: 20px;
            color: #6c757d;
            gap: 15px;
        }
        .post-author {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .post-author-name {
            font-weight: bold;
            color: #212529;
            text-decoration: none;
        }
        .post-author-name:hover {
            text-decoration: underline;
        }
        .post-stats {
            font-size: 0.9rem;
        }
        .post-content {
            padding: 30px;
            line-height: 1.8;
            font-size: 1.1rem;
        }
        .post-footer {
            padding: 20px;
            border-top: 1px solid #e9ecef;
            display: flex;
            justify-content: flex-end;
        }
        .follow-btn {
            padding: 3px 10px;
            font-size: 0.8rem;
        }
        .bookmark-btn {
            padding: 5px 15px;
        }
        .alert-float {
            position: fixed;
            top: 80px;
            right: 20px;
            z-index: 1000;
            max-width: 300px;
        }
        .post-divider {
            color: #6c757d;
            margin: 0 5px;
        }
    </style>
</head>
<body>
    <?php 
    include "../inc/login_nav.inc.php";
    ?>
    
    <?php if (!empty($followMessage)): ?>
        <div class="alert alert-info alert-dismissible fade show alert-float" role="alert">
            <?= htmlspecialchars($followMessage) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>
    
    <?php if (!empty($bookmarkMessage)): ?>
        <div class="alert alert-info alert-dismissible fade show alert-float" role="alert">
            <?= htmlspecialchars($bookmarkMessage) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>
    
    <div class="container">
        <div class="post-container">
            <div class="post-header">
                <h1 class="post-title"><?= htmlspecialchars($post['title']) ?></h1>
                
                <div class="post-meta">
                    <div class="post-author">
                        <a href="author_profile.php?id=<?= $post['author_id'] ?>" class="post-author-name">
                            <?= htmlspecialchars($post['fname'] . ' ' . $post['lname']) ?>
                        </a>
                        
                        <?php if ($post['owner_id'] != $_SESSION['user_id']): ?>
                            <form method="POST" action="" class="d-inline">
                                <input type="hidden" name="author_id" value="<?= $post['owner_id'] ?>">
                                <?php if ($isFollowing): ?>
                                    <input type="hidden" name="follow_action" value="unfollow">
                                    <button type="submit" class="btn btn-outline-secondary btn-sm follow-btn">
                                        Following
                                    </button>
                                <?php else: ?>
                                    <input type="hidden" name="follow_action" value="follow">
                                    <button type="submit" class="btn btn-primary btn-sm follow-btn">
                                        Follow
                                    </button>
                                <?php endif; ?>
                            </form>
                        <?php endif; ?>
                    </div>
                    
                    <div class="post-stats">
                        <span><?= $read_time ?> min read</span> • 
                        <span>Published <?= $formatted_date ?></span>
                    </div>
                </div>
            </div>
            
            <div class="post-content">
                <?= nl2br(htmlspecialchars($post['content'])) ?>
            </div>
            
            <?php if (isset($_SESSION['is_admin']) && $_SESSION['is_admin'] == 1): ?>
                <div class="card mb-4 border-warning">
                    <div class="card-header bg-warning text-dark">
                        <h5 class="mb-0">Admin Actions</h5>
                    </div>
                    <div class="card-body">
                        <p class="text-muted mb-2">As an admin, you have additional actions available:</p>
                        <button type="button" class="btn btn-danger" 
                                data-bs-toggle="modal" data-bs-target="#adminDeletePostModal">
                            <i class="bi bi-trash"></i> Delete Post
                        </button>
                    </div>
                </div>

                <!-- Admin Delete Post Modal -->
                <div class="modal fade" id="adminDeletePostModal" tabindex="-1" aria-labelledby="adminDeletePostModalLabel" aria-hidden="true">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="adminDeletePostModalLabel">Confirm Delete</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <p>Are you sure you want to delete this post?</p>
                                <p class="text-danger">This action cannot be undone.</p>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                <form action="../process/delete_post.php" method="post">
                                    <input type="hidden" name="post_id" value="<?= $post['post_id'] ?>">
                                    <button type="submit" class="btn btn-danger">Delete</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
            
            <div class="post-footer">
                <form method="POST" action="">
                    <?php if ($isBookmarked): ?>
                        <input type="hidden" name="bookmark_action" value="unbookmark">
                        <button type="submit" class="btn btn-outline-secondary bookmark-btn">
                            <i class="fas fa-bookmark"></i> Saved to Library
                        </button>
                    <?php else: ?>
                        <input type="hidden" name="bookmark_action" value="bookmark">
                        <button type="submit" class="btn btn-outline-primary bookmark-btn">
                            <i class="far fa-bookmark"></i> Save to Library
                        </button>
                    <?php endif; ?>
                </form>
            </div>
        </div>
    </div>
    
    <?php include "../inc/footer.inc.php"; ?>
</body>
</html>
