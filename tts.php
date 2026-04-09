<?php
require 'config.php'; // 复用你现有配置

// 🔧 配置：Fish Audio API
$FISH_API_URL = "https://api.fish.audio/v1/tts";
$FISH_API_KEY = "你的Fish Audio API Key"; // 替换成你自己的

// 读取音色文件
$jsonPath = __DIR__ . '/quanming.json';
$voiceData = json_decode(file_get_contents($jsonPath), true);
$fishVoices = $voiceData['彬专属音色']['list'] ?? [];

// 🚀 处理API合成请求
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['do_tts'])) {
    header('Content-Type: application/json');

    $text = trim($_POST['text'] ?? '');
    $vid = $_POST['vid'] ?? '';
    $speed = (float)($_POST['speed'] ?? 1.0);
    $pitch = (float)($_POST['pitch'] ?? 1.0);
    $volume = (float)($_POST['volume'] ?? 1.0);

    if (!$text || !$vid) {
        echo json_encode(['ok' => false, 'msg' => '参数缺失']);
        exit;
    }

    // ✅ 修复：MP3 仅支持 32000/44100 Hz，这里用 44100（音质更好）
    $payload = [
        'text' => $text,
        'reference_id' => $vid,
        'format' => 'mp3',
        'sample_rate' => 44100,
        'mp3_bitrate' => 128,
        'speed' => $speed,
        'pitch' => $pitch,
        'volume' => $volume
    ];

    $headers = [
        'Authorization: Bearer ' . $FISH_API_KEY,
        'Content-Type: application/json'
    ];

    $ch = curl_init($FISH_API_URL);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode === 200) {
        echo json_encode([
            'ok' => true,
            'audio' => 'data:audio/mp3;base64,' . base64_encode($response)
        ]);
    } else {
        echo json_encode([
            'ok' => false,
            'msg' => '合成失败：' . $response
        ]);
    }
    exit;
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>彬 · Fish Audio 专属音色</title>
<style>
    *{margin:0;padding:0;box-sizing:border-box;font-family:"Microsoft YaHei",sans-serif}
    body{
        background:linear-gradient(135deg,#667eea 0%,#764ba2 100%);
        min-height:100vh;padding:20px
    }
    .wrap{
        max-width:900px;
        margin:0 auto;
        background:#fff;
        border-radius:20px;
        padding:40px;
        box-shadow:0 10px 30px rgba(0,0,0,.15)
    }
    h1{
        text-align:center;
        margin-bottom:30px;
        background:linear-gradient(135deg,#667eea 0%,#764ba2 100%);
        -webkit-background-clip:text;
        -webkit-text-fill-color:transparent
    }
    textarea{
        width:100%;
        height:220px;
        padding:15px;
        border:2px solid #eef2f7;
        border-radius:12px;
        outline:none;
        font-size:16px;
        resize:none;
        margin-bottom:20px
    }
    textarea:focus{border-color:#667eea}
    .row{
        display:flex;
        gap:15px;
        margin-bottom:20px;
        flex-wrap:wrap
    }
    .col{flex:1;min-width:200px}
    label{
        display:block;
        margin-bottom:8px;
        font-weight:500;
        color:#333
    }
    select,button{
        width:100%;
        padding:12px 15px;
        border-radius:10px;
        border:1px solid #ddd;
        font-size:15px
    }
    button{
        background:linear-gradient(135deg,#667eea 0%,#764ba2 100%);
        color:#fff;
        border:none;
        cursor:pointer
    }
    .btn-secondary{
        background:linear-gradient(135deg,#ff6b6b 0%,#ee5a52 100%)
    }
    .range-wrap{margin-bottom:15px}
    .range-label{
        display:flex;
        justify-content:space-between;
        margin-bottom:6px;
        color:#555
    }
    input[type="range"]{
        width:100%;
        height:6px;
        border-radius:3px;
        background:#eef2f7;
        outline:none
    }
    .player{
        margin-top:20px;
        text-align:center
    }
    audio{
        width:100%;
        margin-top:10px
    }
</style>
</head>
<body>
<div class="wrap">
    <h1>🎤 彬 · Fish Audio 专属音色合成</h1>

    <textarea id="text" placeholder="请输入要合成的文本..."></textarea>

    <div class="row">
        <div class="col">
            <label>选择音色</label>
            <select id="vid">
                <?php foreach ($fishVoices as $v): ?>
                    <option value="<?= htmlspecialchars($v['vid']) ?>">
                        <?= htmlspecialchars($v['name']) ?> - <?= htmlspecialchars($v['desc']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>

    <div class="range-wrap">
        <div class="range-label">
            <span>语速</span>
            <span id="rateVal">1.0</span>
        </div>
        <input type="range" id="speed" min="0.5" max="2" step="0.1" value="1">
    </div>

    <div class="range-wrap">
        <div class="range-label">
            <span>音调</span>
            <span id="pitchVal">1.0</span>
        </div>
        <input type="range" id="pitch" min="0.5" max="2" step="0.1" value="1">
    </div>

    <div class="range-wrap">
        <div class="range-label">
            <span>音量</span>
            <span id="volVal">1.0</span>
        </div>
        <input type="range" id="volume" min="0" max="2" step="0.1" value="1">
    </div>

    <div class="row">
        <div class="col">
            <button id="btnGen">▶ 生成并播放</button>
        </div>
        <div class="col">
            <button id="btnDownload" class="btn-secondary">💾 下载 MP3</button>
        </div>
    </div>

    <div class="player" id="player" style="display:none;">
        <audio id="audioPlayer" controls>
    </div>
</div>

<script>
const speed = document.getElementById('speed');
const pitch = document.getElementById('pitch');
const volume = document.getElementById('volume');
const rateVal = document.getElementById('rateVal');
const pitchVal = document.getElementById('pitchVal');
const volVal = document.getElementById('volVal');

speed.oninput = () => rateVal.textContent = speed.value;
pitch.oninput = () => pitchVal.textContent = pitch.value;
volume.oninput = () => volVal.textContent = volume.value;

let audioUrl = '';

document.getElementById('btnGen').onclick = async () => {
    const text = document.getElementById('text').value.trim();
    const vid = document.getElementById('vid').value;

    if (!text) return alert('请输入文本');

    const res = await fetch(location.pathname, {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams({
            do_tts: '1',
            text,
            vid,
            speed: speed.value,
            pitch: pitch.value,
            volume: volume.value
        })
    });

    const data = await res.json();
    if (data.ok) {
        audioUrl = data.audio;
        document.getElementById('player').style.display = 'block';
        document.getElementById('audioPlayer').src = audioUrl;
        document.getElementById('audioPlayer').play();
    } else {
        alert(data.msg || '生成失败');
    }
};

document.getElementById('btnDownload').onclick = () => {
    if (!audioUrl) return alert('请先生成音频');
    const a = document.createElement('a');
    a.href = audioUrl;
    a.download = '彬专属音色_tts.mp3';
    a.click();
};
</script>
</body>
</html>