# Swarm Rebuild for WordPress — 状态快照

> 用途:上下文压缩后的恢复锚点。此文件 + git 历史 + SDD 台账构成完整记录。
> 更新:2026-11-09 · 版本 **0.2.1** · 分支 `build/wordpress-plugin`

## 1. 项目

Chrome 扩展 Swarm Rebuild 的 WordPress 插件版:页面被擦成画布,一群光标实时重建(十字线拉框/打字/钢笔线/标尺/⌥拖复制+Web Audio 音效)。**0.2.0 定性转向产品目标:打开网站即播、绘制完的元素可点击跳转、背景全程可见、控制统一到左下角卡片**(参照 `swarm-cursors-skills/src/` demo 的 SceneInteractions 机制)。定位:可发布 wordpress.org 的产品(**发布被上游授权阻断,见 §6**)。

- 仓库:`D:\Repository\misc\swarm-cursors\swarm-rebuild-wp\`(git,插件文件在根)
- 规格:`docs/superpowers/specs/2026-11-09-wordpress-swarm-rebuild-design.md`(0.1 权威)
- 计划:`docs/superpowers/plans/2026-11-09-wordpress-swarm-rebuild.md`
- 台账(全部裁定/根因记录):`D:\Repository\misc\swarm-cursors\.superpowers\sdd\2026-11-09-wordpress-swarm-rebuild\progress.md`
- 测试站:`https://www.mondayice.com/`(用户已给登录态,IAB 内可操作)
- 本地 harness:`tests/harness/index.html`(背景画布+链接页)+ `plain.html`(回归页),`python -m http.server` 于仓库根后配合引擎直注测试

## 2. 已实现功能(0.2.0)

| 功能 | 说明 |
|---|---|
| 每次都播 | 打开范围内页面即触发(0.2.0 删除"整站首访一次");判定链 reduced-motion > 访客关闭 > 播放 |
| **绘制完即可点击** | 可见 `a[href]`(剔 javascript:)采集为交互区;walk 挂标 + **inline 链接几何兜底**(纯 inline 链接会并入 text run,walk 不可达,按矩形相交找重叠 items 的任务作 deps);任务全 done → 代理 `<a>` 淡入点亮(`pointer-events:auto`),点击:外链 `stop(false)+location.href`、`_blank` 同手势 `window.open`、`#锚点` `stop(true)` 淡回后 scrollIntoView;上限 400;点链接不算 opt-out |
| **背景透出(修复版)** | 采集前找 keepers(fixed+≥95% 视口+canvas/img/video/svg,上限 8)→ walk 跳过其子树(不重绘不隐藏)→ **采集之后**才隐藏内容(0.1.2 根因:先隐藏→capture 跳过 hidden→0 层)→ `.bg` 淡出透出活背景;无 keepers(普通站点)自动退回 0.1.1 行为;恢复点三处冗余:run 开头/destroy/restart |
| 设置(5 项) | 生效范围(首页/URL/整站+列表)、结束后自动回到真实页面(默认开;关=停留在可点击重建页,停留 2.5s 硬编码)、默认开启声音、**默认开启巡览**(0.2.1,`tour_default`;经 config→`ENGINE_CFG.tourDefault`→引擎,访客卡片仍可每轮切)、在移动端启用;6 键 `scope/scope_urls/auto_return/sound_default/tour_default/mobile_enabled`,旧键 sanitize 直接丢弃 |
| 卡片=唯一控制面 | 左下角,重放/关闭动画(播放中=跳回真实页,空闲=本浏览器不再自动播)/声音(**运行中即时生效** `setSound`+prefs)/巡览(`setTour`,每轮默认开,run 开始同步状态)/收起到左缘;数组驱动模板可扩展 |
| HUD 仅状态条 | 引擎 HUD 六按钮全部移除;保留 光标头像/进度/"N agents · d/t layers"/"N 位光标用时 X 秒";Esc=退出+M=切声音保留 |
| 引擎 API | `toggle/start/stop/goLive/onComplete/setSound/setTour/app`;`SwarmRebuildEngineConfig{strings,soundDefault}` 注入前设置 |
| URL 参数 | `?swarm=1` 手动触发(触发后 replaceState 清参);快捷键设置项已删 |
| 短代码 | `[swarm_rebuild autoplay]` 不变(DOM marker 合并,含 `_elementor_data`) |
| 巡览/超限/SEO | 巡览默认开;scrollHeight>40000px 或元素>6000 连引擎都不加载;真实 DOM 永不替换;reduced-motion 无条件跳过 |
| i18n | zh_CN 内置(pot/po/mo,29 词条);引擎 t() 5 键(status)+卡片 9 键;`load_plugin_textdomain` 于 init |

## 3. 架构契约(勿改名)

- **config JSON**:`#swarm-rebuild-config`(wp_head@2 打印):`scopeActive, autoplay(=范围内), autoReturn, soundDefault, tourDefault, engineUrl(带?ver), strings{14键}`
- **引擎自屏敝**:walk 入口与链接兜底均跳过 `#swarm-rebuild-card-host`(0.2.1 修复:否则动画里重绘出"第二张卡")
- **localStorage**:`srOptOut/srCardCollapsed/srSound`(显式 '1'/'0');`srIntroSeen` 已随"每次都播"删除
- **option**:`sr_settings`(5 键);类前缀 `SR_`,常量 `SWARM_REBUILD_*`
- 两层脚本:loader(ESM,wp_enqueue_script_module)+ 4 个纯模块(decide/precheck/prefs/engine-load)+ 引擎(经典脚本惰性注入)
- **引擎链接代理契约**:captureModel 返回 `links[]`(walk 挂标 `curLink`/`it.act` + 几何兜底,`L={el,href,target,layer,box,items,deps,on,proxy}`);View.mount 建 `.acts.w`(world 内,随滚动 transform)/`.acts.v`(固定);Engine.setModel 建 `taskLinks` 反查,任务完成 choke point(`c.task.state="done"` 处)调 `onTaskDone` 递减,归零点亮;App.bind 事件委托两个 acts 容器
- **Esc opt-out 链路**:loader 的 Esc 监听必须挂 **window+capture**(引擎同 target 的 stopPropagation 不阻断同 target 监听,但会阻断 document 级)

## 4. 测试与本地环境

- PHPUnit **32/32**、node --test **20/20**(0.2.0 全绿)
- PHP:`D:\PHP8.4.27\php.exe`(未入 PATH,php.ini 已启用 mbstring);PHPUnit:仓库根 `phpunit.phar`;msgfmt 在 Git Bash 可用(`msgfmt xx.po -o xx.mo`)
- 命令:`export PATH="/d/PHP8.4.27:$PATH" && php phpunit.phar`;`node --test tests/js/*.test.js`
- **IAB rAF 冻结怪癖**:IAB 内引擎长运行后 rAF 可能被宿主节流(setTimeout 正常、visibilityState 谎报 visible),页面 reload 后立即注入即可正常运行;二次运行可能延迟 ~45s 自行恢复——判引擎"卡死"前先验 rAF 存活
- harness 验收记录(0.2.0 全过):背景透出视觉确认、代理渐进点亮(21/34 层时 5/8 亮)、锚点点击跳转恢复、Esc 恢复+opt-out、二次运行无残留、普通页回归、卡片声音/巡览即时切换

## 5. 部署(这台主机的特殊规矩)

**OPcache 冻结旧路径字节码**(validate_timestamps=0 类配置,面板重启无效,疑含文件缓存):**每次发版必须换新目录名** `swarm-rebuild-rN`(git archive --prefix),用户删旧装新。同路径重装无效。

- 当前应部署:**`dist/swarm-rebuild-r7-0.2.1.zip`**(未交付用户安装;线上现为 r6-0.2.0,已验证)
- 站点有 WP-Super-Cache(装后清缓存)与 DogeCloud CDN;删除插件触发 uninstall → **设置重置**(5 项需重配:范围/自动返回/声音/巡览/移动端)
- 打包:`git archive --prefix=swarm-rebuild-rN/ -o dist/... HEAD`(tests 等已 export-ignore)

## 6. 未决/已知限制

1. **发布阻断**:需联系 Joel Califa 取得书面授权;readme 已留声明位。
2. 外链/`_blank` 点击在 harness 未实点(与锚点同一 stop+navigate 代码路径,静态核对无误);autoReturn 2.5s 定时器未在 IAB 完整跑完(逻辑同 0.1.4 已测路径)。
3. keepers 判定收窄为 canvas/img/video/svg(计划里的"带 background-image 无文字"分支因误伤风险未实现);sticky 未贴顶/内嵌滚动容器里的链接 layer 判定为启发式。
4. 审查 deferred minors:activation-hook 占位、pot/readme 占位值、引擎 %d 译者注释。
5. 声音卡片即时生效依赖引擎已加载(未加载时仅写 prefs,下次生效)——已按设计接受。

## 7. 版本史

| 版本 | 内容 |
|---|---|
| 0.1.0 | 全部基础功能(T1-T8+终审修复) |
| 0.1.1 | 修复 wp_head@1 竞态 |
| 0.1.2 | 透明画布+中文(已整体 revert,根因存档;0.2.0 已按修复路径重做) |
| 0.1.3 | 仅中文翻译(基线=0.1.1) |
| 0.1.4 | = 0.1.3 + 巡览默认开启 |
| 0.2.0 | 产品重定位:每次都播+绘制完可点击+背景透出(修复版)+设置精简+卡片唯一控制面(线上已验证) |
| **0.2.1** | 修复动画内重绘"第二张卡"(walk/兜底跳过卡片宿主)+ 巡览默认开关入后台(当前) |
