<?php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'resident') {
    header("Location: login.php");
    exit();
}

$serverName = "LAPTOP-8KOIBQER\SQLEXPRESS";
$connectionOptions = ["Database" => "SocialMedia", "Uid" => "", "PWD" => ""];
$conn = sqlsrv_connect($serverName, $connectionOptions);

$userId   = (int)$_SESSION['user_id'];
$postId   = isset($_POST['post_id'])   ? (int)$_POST['post_id']   : 0;
$redirect = isset($_POST['redirect'])  ? $_POST['redirect']       : 'residentcommunity.php';
$action   = isset($_POST['action'])    ? trim($_POST['action'])    : 'react';

$validRedirects = ['residentcommunity.php', 'residentprofile.php'];
$redirectBase   = explode('?', $redirect)[0];
if (!in_array($redirectBase, $validRedirects)) {
    $redirect = 'residentcommunity.php';
}

$regSql  = "SELECT FIRST_NAME, LAST_NAME FROM REGISTRATION WHERE USER_ID = ?";
$regRow  = sqlsrv_fetch_array(sqlsrv_query($conn, $regSql, [$userId]), SQLSRV_FETCH_ASSOC);
$fullName = $regRow ? rtrim($regRow['FIRST_NAME']) . ' ' . rtrim($regRow['LAST_NAME']) : 'A resident';

if ($action === 'comment' && !empty(trim($_POST['comment_body'] ?? ''))) {
    $commentBody = trim($_POST['comment_body']);

    sqlsrv_query($conn,
        "INSERT INTO COMMENTS (POST_ID, USER_ID, BODY, CREATED_AT) VALUES (?, ?, ?, GETDATE())",
        [$postId, $userId, $commentBody]
    );

    $ownerRow = sqlsrv_fetch_array(
        sqlsrv_query($conn, "SELECT USER_ID FROM POSTS WHERE POST_ID = ?", [$postId]),
        SQLSRV_FETCH_ASSOC
    );
    if ($ownerRow && (int)$ownerRow['USER_ID'] !== $userId) {
        $msg = $fullName . ' commented on your post.';
        sqlsrv_query($conn,
            "INSERT INTO NOTIFICATIONS (USER_ID, FROM_USER_ID, TYPE, REFERENCE_ID, MESSAGE, IS_READ, CREATED_AT) VALUES (?, ?, 'COMMENT', ?, ?, 0, GETDATE())",
            [$ownerRow['USER_ID'], $userId, $postId, $msg]
        );
    }

    header("Location: " . $redirect);
    exit();
}

$validTypes = ['LIKE', 'LOVE', 'HAHA', 'WOW', 'SAD', 'ANGRY'];
$reactionType = isset($_POST['reaction_type']) ? strtoupper(trim($_POST['reaction_type'])) : 'LIKE';
if (!in_array($reactionType, $validTypes)) $reactionType = 'LIKE';

$existing = sqlsrv_fetch_array(
    sqlsrv_query($conn,
        "SELECT LIKE_ID, REACTION_TYPE FROM LIKES WHERE POST_ID = ? AND USER_ID = ?",
        [$postId, $userId]
    ),
    SQLSRV_FETCH_ASSOC
);

if ($existing) {
    $existingType = rtrim($existing['REACTION_TYPE']);
    if ($existingType === $reactionType) {
        sqlsrv_query($conn,
            "DELETE FROM LIKES WHERE POST_ID = ? AND USER_ID = ?",
            [$postId, $userId]
        );
    } else {
        sqlsrv_query($conn,
            "UPDATE LIKES SET REACTION_TYPE = ? WHERE POST_ID = ? AND USER_ID = ?",
            [$reactionType, $postId, $userId]
        );

        $ownerRow = sqlsrv_fetch_array(
            sqlsrv_query($conn, "SELECT USER_ID FROM POSTS WHERE POST_ID = ?", [$postId]),
            SQLSRV_FETCH_ASSOC
        );
        if ($ownerRow && (int)$ownerRow['USER_ID'] !== $userId) {
            $reactionLabels = ['LIKE' => 'liked', 'LOVE' => 'loved', 'HAHA' => 'laughed at', 'WOW' => 'wowed at', 'SAD' => 'reacted sadly to', 'ANGRY' => 'reacted angrily to'];
            $label = $reactionLabels[$reactionType] ?? 'reacted to';
            $msg   = $fullName . ' ' . $label . ' your post.';
            sqlsrv_query($conn,
                "INSERT INTO NOTIFICATIONS (USER_ID, FROM_USER_ID, TYPE, REFERENCE_ID, MESSAGE, IS_READ, CREATED_AT) VALUES (?, ?, 'LIKE', ?, ?, 0, GETDATE())",
                [$ownerRow['USER_ID'], $userId, $postId, $msg]
            );
        }
    }
} else {
    sqlsrv_query($conn,
        "INSERT INTO LIKES (POST_ID, USER_ID, REACTION_TYPE, CREATED_AT) VALUES (?, ?, ?, GETDATE())",
        [$postId, $userId, $reactionType]
    );

    $ownerRow = sqlsrv_fetch_array(
        sqlsrv_query($conn, "SELECT USER_ID FROM POSTS WHERE POST_ID = ?", [$postId]),
        SQLSRV_FETCH_ASSOC
    );
    if ($ownerRow && (int)$ownerRow['USER_ID'] !== $userId) {
        $reactionLabels = ['LIKE' => 'liked', 'LOVE' => 'loved', 'HAHA' => 'laughed at', 'WOW' => 'wowed at', 'SAD' => 'reacted sadly to', 'ANGRY' => 'reacted angrily to'];
        $label = $reactionLabels[$reactionType] ?? 'reacted to';
        $msg   = $fullName . ' ' . $label . ' your post.';
        sqlsrv_query($conn,
            "INSERT INTO NOTIFICATIONS (USER_ID, FROM_USER_ID, TYPE, REFERENCE_ID, MESSAGE, IS_READ, CREATED_AT) VALUES (?, ?, 'LIKE', ?, ?, 0, GETDATE())",
            [$ownerRow['USER_ID'], $userId, $postId, $msg]
        );
    }
}

header("Location: " . $redirect);
exit();
?>