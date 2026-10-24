
<?php
require_once 'db.php';
require_once 'GeminiService.php';

// ------------------------------------------------------------------------
// THAY API KEY CỦA BẠN VÀO ĐÂY (Lấy từ https://aistudio.google.com/)
// ------------------------------------------------------------------------
$apiKey = "AQ.Ab8RN6KiaRhKViNETIkVGmxCZjt5x-Qn1uQdixHFKBjAWrmPlw"; 
$modelName = "gemini-3.6-flash";

$errorMessage = "";

// Xử lý khi người dùng gửi tin nhắn
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['prompt'])) {
    $userPrompt = trim($_POST['prompt']);

    // 1. Gọi Gemini API bằng cURL
    $gemini = new GeminiService($apiKey, $modelName);
    $result = $gemini->generateContent($userPrompt);

    if ($result['success']) {
        $aiResponse = $result['data'];

        // 2. Lưu log vào MySQL bằng PDO
        try {
            $stmt = $pdo->prepare("INSERT INTO api_logs (prompt, response, model_used) VALUES (:prompt, :response, :model)");
            $stmt->execute([
                ':prompt'   => $userPrompt,
                ':response' => $aiResponse,
                ':model'    => $modelName
            ]);
        } catch (PDOException $e) {
            $errorMessage = "Lỗi CSDL: " . $e->getMessage();
        }
    } else {
        $errorMessage = "Lỗi Google API: " . $result['message'];
    }
}

// Lấy toàn bộ lịch sử hỏi đáp từ CSDL bằng PDO
$stmtHistory = $pdo->query("SELECT * FROM api_logs ORDER BY id ASC");
$chatHistory = $stmtHistory->fetchAll();

// Hàm chuyển đổi Markdown cơ bản sang HTML để hiển thị đẹp không bị lỗi dấu #, *
function parseMarkdown($text) {
    // Chống XSS an toàn cơ bản nhưng giữ lại định dạng
    $text = htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
    
    // Tiêu đề (### Text -> <h4>Text</h4>)
    $text = preg_replace('/^### (.*?)$/m', '<h4 style="margin: 10px 0 5px 0; color: #1a73e8;">$1</h4>', $text);
    $text = preg_replace('/^## (.*?)$/m', '<h3 style="margin: 12px 0 6px 0; color: #1a73e8;">$1</h3>', $text);
    
    // In đậm (**text** -> <strong>text</strong>)
    $text = preg_replace('/\*\*(.*?)\*\*/s', '<strong>$1</strong>', $text);
    
    // Danh sách gạch đầu dòng (* text -> <li>text</li>)
    $text = preg_replace('/^\* (.*?)$/m', '<li style="margin-left: 20px; margin-bottom: 4px;">$1</li>', $text);
    
    // Đường kẻ ngang (---)
    $text = preg_replace('/^---$/m', '<hr style="border: 0; border-top: 1px solid #e0e0e0; margin: 10px 0;">', $text);
    
    // Xuống dòng
    $text = nl2br($text);
    
    return $text;
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Trợ lý Google Gemini AI</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif; background: #f8f9fa; display: flex; height: 100vh; color: #202124; }
        
        /* Sidebar lịch sử */
        .sidebar { width: 260px; background: #f0f4f9; border-right: 1px solid #e0e0e0; display: flex; flex-direction: column; padding: 15px; }
        .sidebar h3 { font-size: 15px; color: #444; margin-bottom: 12px; font-weight: 600; }
        .history-list { overflow-y: auto; flex-grow: 1; }
        .history-item { background: #ffffff; padding: 10px; border-radius: 8px; margin-bottom: 8px; font-size: 13px; color: #333; border: 1px solid #e0e0e0; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }

        /* Khung chat chính */
        .chat-container { flex-grow: 1; display: flex; flex-direction: column; height: 100vh; background: #ffffff; }
        .chat-header { padding: 16px 24px; border-bottom: 1px solid #e0e0e0; background: #fff; font-size: 18px; font-weight: 600; color: #1a73e8; }
        
        /* Tin nhắn */
        .chat-messages { flex-grow: 1; overflow-y: auto; padding: 20px 10%; display: flex; flex-direction: column; gap: 16px; }
        .message { max-width: 85%; line-height: 1.6; font-size: 15px; word-break: break-word; }
        .message.user { align-self: flex-end; background: #f0f4f9; color: #1f1f1f; padding: 12px 18px; border-radius: 18px 18px 2px 18px; white-space: pre-wrap; }
        .message.ai { align-self: flex-start; background: #ffffff; color: #202124; padding: 12px 18px; border-radius: 18px 18px 18px 2px; border: 1px solid #e0e0e0; box-shadow: 0 1px 3px rgba(0,0,0,0.05); }
        .message.ai b { color: #1a73e8; display: block; margin-bottom: 4px; font-size: 14px; }

        /* Báo lỗi */
        .error-banner { background: #fce8e6; color: #c5221f; padding: 12px 20px; border-radius: 8px; margin: 10px 10%; font-size: 14px; border: 1px solid #fad2cf; }

        /* Khu vực nhập liệu */
        .chat-input-area { padding: 15px 10%; background: #fff; border-top: 1px solid #e0e0e0; }
        .input-form { display: flex; background: #f0f4f9; border-radius: 24px; padding: 6px 14px; align-items: center; border: 1px solid transparent; }
        .input-form:focus-within { background: #fff; border-color: #1a73e8; box-shadow: 0 1px 6px rgba(32,33,36,0.28); }
        
        textarea { flex-grow: 1; border: none; background: transparent; resize: none; height: 28px; outline: none; font-size: 15px; font-family: inherit; padding-top: 4px; }
        button { background: #1a73e8; color: white; border: none; width: 34px; height: 34px; border-radius: 50%; cursor: pointer; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
        button:hover { background: #1557b0; }
        button svg { width: 16px; height: 16px; fill: white; }

        .empty-state { text-align: center; color: #5f6368; margin: auto; }
        .empty-state h2 { font-size: 26px; color: #1a73e8; margin-bottom: 8px; }
    </style>
</head>
<body>

    <!-- Sidebar lịch sử -->
    <div class="sidebar">
        <h3>💬 Lịch sử hỏi đáp</h3>
        <div class="history-list">
            <?php if (count($chatHistory) > 0): ?>
                <?php foreach ($chatHistory as $item): ?>
                    <div class="history-item" title="<?= htmlspecialchars($item['prompt']) ?>">
                        <?= htmlspecialchars($item['prompt']) ?>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p style="font-size: 13px; color: #888; text-align: center; margin-top: 20px;">Chưa có lịch sử nào.</p>
            <?php endif; ?>
        </div>
    </div>

    <!-- Khung Chat -->
    <div class="chat-container">
        <div class="chat-header">
            ✨ Trợ lý Google Gemini AI
        </div>

        <?php if ($errorMessage): ?>
            <div class="error-banner"><?= htmlspecialchars($errorMessage) ?></div>
        <?php endif; ?>

        <!-- Danh sách tin nhắn -->
        <div class="chat-messages" id="chatBox">
            <?php if (count($chatHistory) > 0): ?>
                <?php foreach ($chatHistory as $chat): ?>
                    <div class="message user">
                        <?= htmlspecialchars($chat['prompt']) ?>
                    </div>
                    <div class="message ai">
                        <b>Gemini AI</b>
                        <div><?= parseMarkdown($chat['response']) ?></div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="empty-state">
                    <h2>Xin chào!</h2>
                    <p>Hãy nhập câu hỏi bên dưới để bắt đầu trò chuyện với AI.</p>
                </div>
            <?php endif; ?>
        </div>

        <!-- Ô nhập câu hỏi -->
        <div class="chat-input-area">
            <form method="POST" class="input-form" id="msgForm">
                <textarea name="prompt" id="promptText" rows="1" required placeholder="Nhập câu hỏi của bạn..."></textarea>
                <button type="submit" title="Gửi">
                    <svg viewBox="0 0 24 24"><path d="M2.01 21L23 12 2.01 3 2 10l15 2-15 2z"/></svg>
                </button>
            </form>
        </div>
    </div>

    <script>
        // Tự động cuộn xuống cuối khung chat
        const chatBox = document.getElementById('chatBox');
        chatBox.scrollTop = chatBox.scrollHeight;

        // Bấm Enter để gửi tin nhắn nhanh
        const textarea = document.getElementById('promptText');
        textarea.addEventListener('keydown', function(e) {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                document.getElementById('msgForm').submit();
            }
        });
    </script>
</body>
</html>