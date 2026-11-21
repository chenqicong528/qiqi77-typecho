<?php
/**
 * 用途：主题入口，只做装配，不放业务
 * 依赖：src/*
 * 被调用：Typecho
 */
if (!defined('__TYPECHO_ROOT_DIR__')) exit;

define('QIQI77_VERSION', '1.0.0');
define('QIQI77_THEME', basename(__DIR__));

require_once __DIR__ . '/src/Autoload.php';
\qiqi77\Autoload::register();

/** 后台：迁移旧配置 + 渲染设置表单 */
function themeConfig($form)
{
    \qiqi77\Bootstrap::prepareAdmin();
    \qiqi77\Options::form($form);

    // 设置页增强：卡片化 + 锚点导航
    echo \qiqi77\Options::searchPanel();
    echo \qiqi77\Options::backupPanel();
    echo '<link rel="stylesheet" href="' . \qiqi77\Options::asset('css/admin.css') . '">';
    // defer：脚本要等表单渲染完再跑，否则查不到 form
    echo '<script defer src="' . \qiqi77\Options::asset('js/admin.js') . '"></script>';
}

/** 后台：文章自定义字段 */
function themeFields($layout)
{
    \qiqi77\Options::fields($layout);
}

/** 请求初始化 */
function themeInit($archive)
{
    \qiqi77\Bootstrap::onInit($archive);
}

/** 新文章发布后自动推送百度（开关在 设置外观 → 收录与统计） */
\Typecho\Plugin::factory('Widget\Contents\Post\Edit')->finishPublish = ['\qiqi77\Site\Baidu', 'onPublish'];
