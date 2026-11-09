# Swarm Rebuild for WordPress

把页面擦成空白画布,一群多人光标实时将其重建——重建过程中已完成的链接即可点击跳转,站点壁纸全程可见。

> Wipe the page to a blank canvas and let a swarm of multiplayer cursors rebuild it live — rebuilt links become clickable while the swarm builds, and the site wallpaper stays visible throughout.

[下载最新版本](https://github.com/mondayice/swarm-rebuild/releases/latest) · 基于 GPLv2-or-later 开源

## 功能

- **打开即播**:范围内的页面每次访问都会自动播放重建动画,可跳过
- **边建边点**:每个链接在重建完成后立即点亮,访客无需等待整页建完
- **壁纸可见**:fixed 画布/视频/图片与伪元素背景层自动检测保留;支持手动 CSS 选择器兜底;可一键关闭回到实色擦除
- **访客卡片**:重放 / 关闭动画 / 声音(即时生效)/ 巡览 / 收起到左缘;动画内仅保留状态条
- **音效**:Web Audio 合成音效,`M` 键或卡片随时开关
- **范围控制**:仅首页 / 指定 URL / 整站;移动端独立开关
- **手动触发**:`?swarm=1` URL 参数、`[swarm_rebuild]` 短代码(含 Elementor 检测)
- **安全**:真实 DOM 永不替换(SEO 无损);`prefers-reduced-motion` 无条件跳过;超大页面优雅跳过

## 安装

1. 从 [Releases](https://github.com/mondayice/swarm-rebuild/releases/latest) 下载 zip
2. WordPress 后台 → 插件 → 安装插件 → 上传 zip → 启用
3. 设置 → Swarm Rebuild,选择生效范围

## 设置项

| 设置 | 说明 |
|---|---|
| 生效范围 | 仅首页(默认)/ 指定 URL / 整站 |
| 指定 URL | 范围选择"指定 URL"时使用,每行一个路径 |
| 结束后自动回到真实页面 | 默认开;关闭则停留在可点击的重建页 |
| 默认开启声音 | 访客可在卡片上静音,选择保留在其浏览器 |
| 默认开启巡览 | 随群体构建自动滚动;访客可在卡片上暂停 |
| 透明背景模式 | 自动检测站点壁纸;关闭 = 始终实色擦除(安全模式) |
| 壁纸元素(CSS 选择器) | 自动检测遗漏时的手动兜底,字段旁有填写帮助 |
| 在移动端启用 | 默认关闭(触屏无光标隐喻) |

## 致谢 / Credits

- 本插件改编自 Chrome 扩展 **Swarm Rebuild** —— [DhvanilPatel/swarm-cursors](https://github.com/DhvanilPatel/swarm-cursors),感谢其快照重建引擎与交互设计
- 该扩展本身是对 **[Swarm](https://califa.github.io/swarm-site/)**(by [Joel Califa](https://joelcalifa.com))的致敬;动画引擎大部分直接改编自其站点源码([califa/swarm-site](https://github.com/califa/swarm-site))。WordPress.org 目录提交暂缓,待取得上游书面许可
- 感谢 WordPress 插件开发社区与 [developer handbook](https://developer.wordpress.org/plugins/)

## 开发

```bash
# PHP 测试(PHP 8.x + PHPUnit)
php phpunit.phar
# JS 测试
npm test
# 打包正式 zip(排除开发文件)
bash scripts/package.sh
```

## License

[GPL-2.0-or-later](license.txt)
