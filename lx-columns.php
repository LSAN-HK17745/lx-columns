<?php
/**
 * Plugin Name: LX Columns
 * Plugin URI:  https://github.com/LSAN-HK17745/lx-columns
 * Description: 栏布局短代码。原 column-shortcodes 的安全替代品——严格白名单、零用户输入透传、无后台编辑器依赖。
 * Version:     1.0.0
 * Author:      墨菲网络安全中心
 * License:     MIT
 * Text Domain: lx-columns
 */

if (!defined('ABSPATH')) exit;

final class LX_Columns {

    /** 允许的栏宽度（白名单，不接受任意标签名） */
    const WIDTHS = array(
        'one_half', 'one_third', 'two_third',
        'one_fourth', 'three_fourth',
        'one_fifth', 'two_fifth', 'three_fifth', 'four_fifth',
        'one_sixth', 'five_sixth',
    );

    /** 允许的 CSS 长度单位 */
    const UNITS = array('px', 'em', 'rem', '%', 'pt', 'vh', 'vw');

    public static function init() {
        foreach (self::WIDTHS as $w) {
            add_shortcode($w, array(__CLASS__, 'render'));
            add_shortcode($w . '_last', array(__CLASS__, 'render'));
        }
    }

    /**
     * 输出与原插件完全一致的 HTML：
     *   <div class='content-column <name>[ last_column]'>{content}</div>
     *   [<div class='clear_column'></div>]  ← 仅 _last 变体
     */
    public static function render($atts, $content = null, $tag = '') {
        // 1) 标签名必须落在白名单内（防止动态注册绕过）
        $base = str_replace('_last', '', $tag);
        if (!in_array($base, self::WIDTHS, true)) {
            return '';
        }

        $atts = shortcode_atts(array(
            'id'      => '',
            'class'   => '',
            'padding' => '',
        ), $atts, $tag);

        // 2) id / class：只允许字母数字下划线横线空格
        $id = self::safe_token($atts['id'], '/[^A-Za-z0-9_-]/');
        $cls = self::safe_token($atts['class'], '/[^A-Za-z0-9_\- ]/');

        // 3) padding：严格按 "长度 长度 长度 长度" 或 "长度" 解析，单位白名单
        $style = self::safe_padding($atts['padding']);

        // 4) 类名（与原版一致：_last → " last_column"）
        $name = $tag;
        $is_last = (strpos($tag, '_last') !== false);
        if ($is_last) {
            $name = str_replace('_last', ' last_column', $tag);
        }

        $id_attr = $id !== '' ? " id='" . esc_attr($id) . "'" : '';
        $cls_attr = $cls !== '' ? esc_attr(' ' . $cls) : '';

        // 5) 内容只走标准短代码解析，不透传未过滤输入
        $inner = do_shortcode(shortcode_unautop(trim((string) $content)));
        if ($style !== '') {
            $inner = '<div style="' . esc_attr($style) . '">' . $inner . '</div>';
        }

        $out = "<div{$id_attr} class='content-column {$name}{$cls_attr}'>{$inner}</div>";
        if ($is_last) {
            $out .= "<div class='clear_column'></div>";
        }
        return $out;
    }

    /** 只保留白名单字符 */
    private static function safe_token($v, $strip_regex) {
        $v = trim((string) $v);
        if ($v === '') return '';
        $v = preg_replace($strip_regex, '', $v);
        return substr($v, 0, 64);
    }

    /**
     * 严格解析 padding。
     * 允许： "10px" / "10px 20px" / "0 10px 0 20px"
     * 拒绝： 任何非 数字+白名单单位 的片段（含 expression/url/var 等）
     */
    private static function safe_padding($raw) {
        $raw = trim((string) $raw);
        if ($raw === '') return '';

        $parts = preg_split('/\s+/', $raw);
        if (!$parts || count($parts) > 4) return '';

        $clean = array();
        foreach ($parts as $p) {
            $val = self::safe_length($p);
            if ($val === null) return '';   // 任一段非法 → 整体放弃（安全优先）
            $clean[] = $val;
        }

        if (count($clean) === 4) {
            $map = array('top', 'right', 'bottom', 'left');
            $style = '';
            foreach ($clean as $i => $v) {
                if ($v !== '' && $v !== '0') $style .= 'padding-' . $map[$i] . ':' . $v . ';';
            }
            return $style;
        }

        return 'padding:' . implode(' ', $clean) . ';';
    }

    /** 单个长度值：必须 数字+单位（或纯 0） */
    private static function safe_length($s) {
        $s = trim($s);
        if ($s === '0' || $s === '0px') return '0';
        if (!preg_match('/^(\d{1,4}(?:\.\d{1,2})?)([a-z%]{0,4})$/i', $s, $m)) return null;
        $num = $m[1];
        $unit = strtolower($m[2]);
        if ($unit === '') $unit = 'px';
        if (!in_array($unit, self::UNITS, true)) return null;
        return $num . $unit;
    }
}

LX_Columns::init();
