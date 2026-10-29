# LX Columns

栏布局短代码的**安全替代品** —— 替代 2017 年的 `column-shortcodes`。

面向仍在维护老 WordPress 站点的团队：原插件早已停止更新，且在 PHP 8 上存在兼容隐患；本插件在**保持输出完全一致**的前提下重写了实现。

## 为什么做这个

原 `column-shortcodes` 0.6.9 存在的问题：

1. **停止更新**（最后版本 2018 年）
2. **padding 属性无校验** —— 直接把用户输入拼进 `style`，`esc_attr()` 只转义 HTML 特殊字符，**挡不住 CSS 注入**
3. **动态短代码注册** —— 通过过滤器可扩展任意标签名，攻击面不明确
4. **加载后台编辑器 JS** —— 前台不需要的代码也在加载

## 本插件做了什么

| 项 | 原版 | 本版 |
|---|---|---|
| 标签名 | 动态注册 | **固定白名单**（11 种宽度） |
| padding | 原样拼接 | **严格解析**：数字 + 白名单单位，任一段非法则整体放弃 |
| id / class | `sanitize_text_field` | **字符白名单** + 64 字符上限 |
| 后台 JS | 加载编辑器按钮 | **完全不加载** |
| 输出 HTML | — | **与原版逐字节一致** |

### 安全性设计

```php
// padding 只接受：数字 + px/em/rem/%/pt/vh/vw
// 任一段非法 → 整体放弃，不输出 style（安全优先，而非降级）
private static function safe_padding($raw) { ... }
```

拒绝 `expression()`、`url()`、`var()`、CSS 变量注入等一切非长度单位。

## 安装

```bash
cd wp-content/mu-plugins
curl -O https://raw.githubusercontent.com/LSAN-HK17745/lx-columns/main/lx-columns.php
```

作为 **mu-plugin** 安装（自动加载，后台无法误删）。

## 用法

```
[one_half]左栏[/one_half]
[one_half_last]右栏[/one_half_last]
```

支持宽度：`one_half` `one_third` `two_third` `one_fourth` `three_fourth` `one_fifth` `two_fifth` `three_fifth` `four_fifth` `one_sixth` `five_sixth`

`_last` 后缀表示该行最后一栏，会自动追加 `last_column` 类并插入清除浮动元素。

### 属性

```
[one_fourth padding="0 20px 0 20px"]内容[/one_fourth]
```

- `padding` —— 1~4 个长度值，按 CSS 简写规则
- `id` —— 元素 id（字母数字下划线横线）
- `class` —— 附加类名（字母数字下划线横线空格）

## 输出对照

```
[one_fourth_last padding="0 20px 0 20px"]...[/one_fourth_last]
```

```html
<div class='content-column one_fourth last_column'>
  <div style="padding-top:0;padding-right:20px;padding-bottom:0;padding-left:20px;">...</div>
</div>
<div class='clear_column'></div>
```

与原版输出**逐字节一致**，可无缝替换。

## 迁移步骤

1. 安装本插件到 `mu-plugins/`
2. 在后台停用 `column-shortcodes`
3. 打开含栏布局的页面，确认排版无变化
4. 确认无误后删除原插件目录

## 环境

- WordPress 5.0+
- PHP 7.4 / 8.0 / 8.1 / 8.2

## 许可

MIT
