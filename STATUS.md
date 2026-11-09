# Swarm Rebuild for WordPress — 状态快照

> 用途:上下文压缩后的恢复锚点。此文件 + git 历史 + SDD 台账构成完整记录。
> 更新:2026-11-09 · 版本 **0.1.4** · 分支 `build/wordpress-plugin`

## 1. 项目

Chrome 扩展 Swarm Rebuild 的 WordPress 插件版:页面被擦成画布,一群光标实时重建(十字线拉框/打字/钢笔线/标尺/⌥拖复制+Web Audio 音效)。定位:可发布 wordpress.org 的产品(**发布被上游授权阻断,见 §6**)。

- 仓库:`D:\Repository\misc\swarm-cursors\swarm-rebuild-wp\`(git,插件文件在根)
- 规格:`docs/superpowers/specs/2026-11-09-wordpress-swarm-rebuild-design.md`(权威)
- 计划:`docs/superpowers/plans/2026-11-09-wordpress-swarm-rebuild.md`
- 台账(全部裁定/根因记录):`D:\Repository\misc\swarm-cursors\.superpowers\sdd\2026-11-09-wordpress-swarm-rebuild\progress.md`
- 测试站:`https://www.mondayice.com/`(用户已给登录态,IAB 内可操作)

## 2. 已实现功能(0.1.4)

| 功能 | 说明 |
|---|---|
| 设置页 | Settings→Swarm Rebuild,8 字段,全中文 |
| 生效范围 | 仅首页(默认)/指定 URL/整站;loader 只在范围内入队,范围管一切 |
| 移动端门 | 默认禁用(UA 判定),短路优先级最高 |
| 短代码 | `[swarm_rebuild autoplay="true|1|yes"]`;入队经 has_shortcode(含 `_elementor_data` meta);autoplay 经 DOM marker `#swarm-rebuild-shortcode` 由 loader 合并 |
| URL 参数 | `?swarm=1` 手动触发,触发后 replaceState 清参 |
| 快捷键 | Alt+Shift+S(`e.code==='KeyS'` 物理键位) |
| 开场模式 | 默认关;判定链 reduced-motion > 访客关闭(localStorage) > 整站首访 > 范围;播完按"开场结束停留"(默认 2.5s)自动 goLive;手动触发永不自动退出 |
| 访客卡片 | 左下角,重放/关闭动画(两层:播放中=跳过,平时=本浏览器不再自动播)/声音/收起到左缘;压在引擎覆盖层之上(start 后重挂 html);声音开关下一**页面加载**生效 |
| 巡览 | **默认开启**(0.1.4),HUD 按钮初始"巡览中/on",完成自动停,访客可关 |
| 超限保护 | loader 预检 scrollHeight>40000px 或元素>6000 → 连引擎都不加载;引擎 4000 层截断为第二道 |
| 引擎 API | `window.SwarmRebuild{toggle,start,stop,goLive,onComplete,app}`;启动受控(无自动 start);`window.SwarmRebuildEngineConfig{strings,soundDefault}` 注入前设置;单次注入后全走 API |
| 引擎 i18n | 22 个可见文案键经 config 下发,英文 fallback;`t(key,fallback,...args)` 顺序 %d |
| 中文化 | zh_CN 内置(languages/zh_CN po+mo,39 词条),`load_plugin_textdomain` 于 init;站点中文则设置页/HUD/卡片全中文 |
| SEO/无障碍 | 真实 DOM 永不替换;reduced-motion 无条件跳过 |
| 发布物 | readme.txt、.pot、GPLv2 license.txt、uninstall.php(delete_option sr_settings)、scripts/package.sh(git archive) |

## 3. 架构契约(勿改名)

- **config JSON**:`#swarm-rebuild-config`(wp_head@2 打印,勿用优先级 1——与 wp_enqueue_scripts 同优先级会被快照迭代吞掉,已踩坑修复):`scopeActive,autoplay,autoplayMode,canvasBackground(值固定'page',设置项已撤销),hotkeyEnabled,cardEnabled,soundDefault,introDelay,engineUrl(带?ver),strings{23键}`
- **localStorage**:`srIntroSeen/srOptOut/srCardCollapsed/srSound`(显式 '1'/'0' 存储)
- **option**:`sr_settings`(8 键);类前缀 `SR_`,常量 `SWARM_REBUILD_*`
- 两层脚本:loader(ESM,wp_enqueue_script_module)+ 4 个纯模块(decide/precheck/prefs/engine-load)+ 引擎(经典脚本惰性注入)

## 4. 测试与本地环境

- PHPUnit **32/32**、node --test **22/22**(0.1.4 全绿)
- PHP:`D:\PHP8.4.27\php.exe`(未入 PATH,php.ini 已启用 mbstring,备份 php.ini.bak-swarm);PHPUnit:仓库根 `phpunit.phar`
- 命令:`export PATH="/d/PHP8.4.27:$PATH" && php phpunit.phar`;`npm test`(node --test 自动发现;直接调用用 `node --test tests/js/*.test.js` glob 形式)
- Node v24;Git Bash;WP 垫片在 `tests/php/bootstrap.php`

## 5. 部署(这台主机的特殊规矩)

**OPcache 冻结旧路径字节码**(validate_timestamps=0 类配置,面板重启无效,疑含文件缓存):**每次发版必须换新目录名** `swarm-rebuild-rN`(git archive --prefix),用户删旧装新。同路径重装无效。

- 当前应部署:**`dist/swarm-rebuild-r5-0.1.4.zip`**
- 站点有 WP-Super-Cache(装后清缓存)与 DogeCloud CDN;删除插件会触发 uninstall → **设置重置**
- 打包:`bash scripts/package.sh`(正式名)+ `git archive --prefix=swarm-rebuild-rN/ -o dist/... HEAD`(站点迭代名)

## 6. 未决/已知限制

1. **发布阻断**:需联系 Joel Califa 取得书面授权(引擎改编自其 Swarm 站,无授权);readme 已留声明位。
2. **透明画布模式已撤销**(用户决定):根因已存档——`hideContentForErase` 曾置于 scan/capture 之前,capture 跳过 visibility:hidden 元素 → 0 图层;重做时把隐藏移到 `mark("capture")` 之后即可(引擎 App.run ~3547 行 scan、~3562 captureModel)。相关代码在 git 历史 efb7fe3。
3. 体验限制(已记 readme/台账):卡片声音开关运行中不热切换(用 HUD 的 M);intro 2.5s 窗口内经 HUD 重放可能被旧定时器打断;侧栏/widget 短代码不扫描(仅主内容+Elementor);IAB 内嵌浏览器报告 reduced-motion → 动画被正确抑制(测试时勿用 IAB 判死活)。
4. 审查 deferred minors:activation-hook 占位未做、pot/readme 占位值(example.com/Contributors)待提交前换、引擎 %d 建议加译者注释、reduced-motion 下卡片重放无反馈。
5. **T9 验收矩阵未完整跑完**(部署验证✓ 含卡片/动画/中文 HUD;范围外页面/移动 UA ✓;关闭两层/收起/整站首访二访/Elementor 降级等项待最终确认)。

## 7. 版本史

| 版本 | 内容 |
|---|---|
| 0.1.0 | 全部基础功能(T1-T8+终审修复) |
| 0.1.1 | 修复 wp_head@1 竞态(config 标签不输出=线上"没生效"的根因) |
| 0.1.2 | 透明画布+中文(已整体 revert,仅存历史) |
| 0.1.3 | 仅中文翻译(基线=0.1.1) |
| **0.1.4** | = 0.1.3 + 巡览默认开启(当前) |
