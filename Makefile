INSTALL_TARGET_PROCESSES = WeChat

# 这里的 SDK 路径需根据你的开发环境配置
# TARGET = iphone:clang:latest:14.0
# ARCHS = arm64 arm64e

include $(THEOS)/makefiles/common.mk

TWEAK_NAME = WeChatTTS

WeChatTTS_FILES = Tweak.x
WeChatTTS_CFLAGS = -fobjc-arc
# WeChatTTS_LDFLAGS = -L./libs -lsilk  # 如果集成外部库

include $(THEOS)/makefiles/tweak.mk
