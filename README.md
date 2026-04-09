# 微信文字转语音 (TTS) 插件开发指南

本插件基于 **Theos** 开发，旨在为微信添加文字转语音功能，支持 **Fish Audio** 接口，并能将 MP3 转换为微信专用的 **Silk** 格式直接发送。

## 核心组件说明

### 1. Tweak.x (核心代码)
*   **UI 注入**：Hook 了 `BaseMsgContentViewController`，在聊天界面右上方添加了一个 "TTS" 按钮。
*   **接口调用**：集成了 `NSURLSession` 直接调用 Fish Audio 官方接口。
*   **音色 ID**：目前代码中硬编码了一个示例 ID，你可以通过解析 `quanming.json` 实现音色选择器。

### 2. 关键难点：Silk 格式转换
微信语音必须是 **Silk v3** 编码。你需要集成 `libsilk` 到你的 dylib 中。
*   **源码推荐**：使用 [kn007/silk-v3-decoder](https://github.com/kn007/silk-v3-decoder) 的 C 语言源码。
*   **集成方法**：
    1.  将 `silk` 编码库编译为静态库 (`.a`) 或直接将源码加入工程。
    2.  在 `convertMP3ToSilk` 方法中调用 `silk_encode`。
    3.  注意：MP3 需要先解码为 PCM (44100Hz, 16bit, 单声道) 再编码为 Silk。

### 3. 微信发送 Hook 逻辑
要实现一键发送，建议 Hook 以下方法：
*   `CMessageMgr` 的 `AddMsg:MsgData:` 方法。
*   构造一个 `CMessageWrap` 对象，设置 `m_uiMessageType` 为 34 (语音消息)。
*   设置 `m_nsFilePath` 为转换后的 Silk 文件路径。

## 编译与安装

### 1. 准备环境
确保你已安装 **Theos** 开发环境：
```bash
export THEOS=/opt/theos
export PATH=$THEOS/bin:$PATH
```

### 2. 编译插件
在项目目录下执行：
```bash
make package
```
编译成功后，`packages` 目录下会生成 `.deb` 文件。

### 3. 注入与签名 (支持全能签/巨魔)
*   **巨魔注入**：将生成的 `.dylib` 文件通过巨魔自带的注入工具注入到微信 IPA 中。
*   **全能签**：上传 IPA 和 `.dylib`，选择“库注入”进行重签名。

## 待优化项
1.  **音色选择器**：解析 `quanming.json` 并使用 `UIPickerView` 展示。
2.  **设置面板**：使用 `PreferenceBundle` 在系统设置或微信设置中保存 API Key。
3.  **音频时长**：发送前需计算 Silk 文件的时长，否则微信显示为 0 秒。
