# 第三方资源与许可声明

本文件列出 ChatBBS 在开发过程中参考或使用的第三方项目，以及相应的许可信息。

---

## Flarum

- **项目**：Flarum — 免费、开源的社区论坛软件
- **官网**：https://flarum.org
- **源码**：https://github.com/flarum/framework
- **许可证**：MIT License，Copyright (c) Flarum Foundation

### 使用范围

ChatBBS 的**登录、注册、找回密码、重置密码**页面（`themes/*/templates/auth/*`、`themes/*/templates/layouts/gateway.php`、`themes/*/assets/css/auth-flarum.css`）在界面设计上参考了 Flarum 的 `LogInModal` / `SignUpModal` 弹窗布局，并沿用了其公开的 CSS 类名命名习惯（`Modal`、`Modal-header`、`Modal-body`、`Modal-footer`、`Form-group`、`FormControl`、`Button--primary`、`Button--block` 等），以便与 Flarum 生态的样式文档对照。

**未复制 Flarum 的源代码或 CSS 文件**：上述页面的 HTML 结构、PHP 模板、CSS 样式数值均为 ChatBBS 自行编写；仅在视觉语言（弹窗形态、表单排布、圆角与间距比例）上做风格借鉴。

### MIT License 全文

```
MIT License

Copyright (c) Flarum Foundation

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all
copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
SOFTWARE.
```

---

## NexusPHP

- **项目**：NexusPHP — 基于 PHP 的 PT 站点 / 论坛程序
- **说明**：ChatBBS 的 `themes/nexus` 主题参考了 NexusPHP 默认模板的**视觉布局**（表格化板块列表、colhead 表头、rowfollow 交替行、info_block 用户信息条），仅作界面风格致敬，不含其源代码；板块图标使用 ChatBBS 自有的 `static/images/forum/*.gif` 资源。

---

## GitHub 标志

- **图形来源**：Simple Icons（https://simpleicons.org），CC0 1.0 Universal
- **商标说明**：GitHub 名称与 Octocat 标志是 GitHub, Inc. 的商标，ChatBBS 仅在页脚使用该标志链接到本项目的 GitHub 仓库（https://github.com/likeweixue/chatbbs），不代表 GitHub 对本项目的认可或赞助。

---

## 其它

- 图标：除页脚 GitHub 标志外，其余图标为 ChatBBS 自绘 SVG（`static/images/retro/icons.svg`），未使用第三方图标库。
- 字体：使用系统字体栈，不内嵌第三方字体文件。
