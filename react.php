<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    if (!empty($_POST['ajax'])) {
        header('Content-Type: application/json');
        echo json_encode(['error' => 'not logged in']);
        exit();
    }
    header('Location: login.php');
    exit();
}

$serverName = "LAPTOP-8KOIBQER\SQLEXPRESS";
$connectionOptions = ["Database" => "SocialMedia", "Uid" => "", "PWD" => ""];
$conn = sqlsrv_connect($serverName, $connectionOptions);

$userId = $_SESSION['user_id'];
$isAjax = !empty($_POST['ajax']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $action = $_POST['action'] ?? 'react';

    if ($action === 'comment') {
        $postId = (int)($_POST['post_id'] ?? 0);
        $body   = trim($_POST['comment_body'] ?? '');
        if ($postId > 0 && $body !== '') {
            sqlsrv_query($conn,
                "INSERT INTO COMMENTS (POST_ID, USER_ID, BODY, CREATED_AT) VALUES (?, ?, ?, GETDATE())",
                [$postId, $userId, $body]
            );
            $ownerRow = sqlsrv_fetch_array(
                sqlsrv_query($conn, "SELECT USER_ID FROM POSTS WHERE POST_ID = ?", [$postId]),
                SQLSRV_FETCH_ASSOC
            );
            if ($ownerRow && (int)$ownerRow['USER_ID'] !== $userId) {
                $nameRow = sqlsrv_fetch_array(
                    sqlsrv_query($conn, "SELECT FIRST_NAME, LAST_NAME FROM REGISTRATION WHERE USER_ID = ?", [$userId]),
                    SQLSRV_FETCH_ASSOC
                );
                $name = $nameRow
                    ? rtrim($nameRow['FIRST_NAME']) . ' ' . rtrim($nameRow['LAST_NAME'])
                    : 'Someone';
                sqlsrv_query($conn,
                    "INSERT INTO NOTIFICATIONS (USER_ID, TYPE, MESSAGE, REFERENCE_ID, IS_READ, CREATED_AT)
                     VALUES (?, 'COMMENT', ?, ?, 0, GETDATE())",
                    [$ownerRow['USER_ID'], $name . ' commented on your post.', $postId]
                );
            }
        }
        $redirect = $_POST['redirect'] ?? 'residentcommunity.php';
        header('Location: ' . $redirect);
        exit();
    }

    $postId      = (int)($_POST['post_id'] ?? 0);
    $reactionType = strtoupper(trim($_POST['reaction_type'] ?? 'LIKE'));

    $allowed = ['LIKE','LOVE','HAHA','WOW','SAD','ANGRY'];
    if (!in_array($reactionType, $allowed)) $reactionType = 'LIKE';

    if ($postId > 0) {
        $existing = sqlsrv_fetch_array(
            sqlsrv_query($conn,
                "SELECT LIKE_ID, REACTION_TYPE FROM LIKES WHERE POST_ID = ? AND USER_ID = ?",
                [$postId, $userId]
            ),
            SQLSRV_FETCH_ASSOC
        );

        $removed = false;

        if ($existing) {
            $existingType = rtrim($existing['REACTION_TYPE']);
            if ($existingType === $reactionType) {
                sqlsrv_query($conn,
                    "DELETE FROM LIKES WHERE LIKE_ID = ?",
                    [$existing['LIKE_ID']]
                );
                $removed = true;
            } else {
                sqlsrv_query($conn,
                    "UPDATE LIKES SET REACTION_TYPE = ? WHERE LIKE_ID = ?",
                    [$reactionType, $existing['LIKE_ID']]
                );
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
                $nameRow = sqlsrv_fetch_array(
                    sqlsrv_query($conn, "SELECT FIRST_NAME, LAST_NAME FROM REGISTRATION WHERE USER_ID = ?", [$userId]),
                    SQLSRV_FETCH_ASSOC
                );
                $name = $nameRow
                    ? rtrim($nameRow['FIRST_NAME']) . ' ' . rtrim($nameRow['LAST_NAME'])
                    : 'Someone';
                sqlsrv_query($conn,
                    "INSERT INTO NOTIFICATIONS (USER_ID, TYPE, MESSAGE, REFERENCE_ID, IS_READ, CREATED_AT)
                     VALUES (?, 'LIKE', ?, ?, 0, GETDATE())",
                    [$ownerRow['USER_ID'], $name . ' reacted to your post.', $postId]
                );
            }
        }

        if ($isAjax) {
            $totalRow = sqlsrv_fetch_array(
                sqlsrv_query($conn, "SELECT COUNT(*) AS CNT FROM LIKES WHERE POST_ID = ?", [$postId]),
                SQLSRV_FETCH_ASSOC
            );
            $total = $totalRow ? (int)$totalRow['CNT'] : 0;

            $summaryStmt = sqlsrv_query($conn,
                "SELECT REACTION_TYPE, COUNT(*) AS CNT FROM LIKES WHERE POST_ID = ? GROUP BY REACTION_TYPE ORDER BY CNT DESC",
                [$postId]
            );
            $summary = [];
            while ($sr = sqlsrv_fetch_array($summaryStmt, SQLSRV_FETCH_ASSOC)) {
                $summary[] = ['type' => rtrim($sr['REACTION_TYPE']), 'cnt' => (int)$sr['CNT']];
            }

            header('Content-Type: application/json');
            echo json_encode([
                'ok'       => true,
                'removed'  => $removed,
                'reaction' => $removed ? null : $reactionType,
                'total'    => $total,
                'summary'  => $summary,
            ]);
            exit();
        }
    }

    $redirect = $_POST['redirect'] ?? 'residentcommunity.php';
    header('Location: ' . $redirect);
    exit();
}

header('Location: residentcommunity.php');
exit();