<?php
// GeminiService.php - Xử lý cURL gọi Google Gemini API
class GeminiService {
    private $apiKey;
    private $model;

    public function __construct($apiKey, $model = "gemini-3.6-flash") {
        $this->apiKey = $apiKey;
        $this->model = $model;
    }

    public function generateContent($prompt) {
        // Endpoint chính thức của Gemini API
        $url = "https://generativelanguage.googleapis.com/v1beta/models/{$this->model}:generateContent?key=" . $this->apiKey;

        // Dữ liệu payload chuẩn dạng JSON
        $payload = json_encode([
            "contents" => [
                [
                    "parts" => [
                        ["text" => $prompt]
                    ]
                ]
            ]
        ]);

        // Cấu hình cURL
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $payload,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Content-Length: ' . strlen($payload)
            ],
            // Bỏ qua SSL certificate lỗi trên localhost (XAMPP)
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
        ]);

        $response = curl_exec($ch);
        $curlError = curl_error($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($curlError) {
            return ["success" => false, "message" => "Lỗi kết nối cURL: " . $curlError];
        }

        $responseData = json_decode($response, true);

        // Kiểm tra xem phản hồi có chứa câu trả lời từ AI không
        if ($httpCode == 200 && isset($responseData['candidates'][0]['content']['parts'][0]['text'])) {
            return [
                "success" => true,
                "data" => $responseData['candidates'][0]['content']['parts'][0]['text']
            ];
        } else {
            // Lấy chi tiết lỗi từ Google API nếu có
            $errorMsg = isset($responseData['error']['message']) ? $responseData['error']['message'] : "HTTP $httpCode - Phản hồi từ Google: " . $response;
            return [
                "success" => false,
                "message" => $errorMsg
            ];
        }
    }
}
?>