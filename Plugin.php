<?php

if (!defined('__TYPECHO_ROOT_DIR__')) exit;

/**
 * ContentManager
 *
 * @package ContentManager
 * @author Shawn
 * @version 1.0.0
 * @link https://shawnzeng.com
 */
class ContentManager_Plugin implements Typecho_Plugin_Interface
{
    public static function activate()
    {
        $db = Typecho_Db::get();
        $prefix = $db->getPrefix();

        // 检查是否已有电影表
        $sql = "SHOW TABLES LIKE '{$prefix}movies'";
        $result = $db->fetchRow($sql);
        if (!$result) {
            // 创建电影表
            $sql = "CREATE TABLE IF NOT EXISTS `{$prefix}movies` (
                `id` INT NOT NULL AUTO_INCREMENT,
                `name` VARCHAR(255) NOT NULL,
                `douban_id` VARCHAR(255) NOT NULL,
                `directors` TEXT NOT NULL,
                `actors` TEXT NOT NULL,
                `genres` TEXT NOT NULL,
                `image_url` VARCHAR(255) NOT NULL,
                `rating` FLOAT NOT NULL,
                PRIMARY KEY (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8;";
            $db->query($sql);
        }

        Helper::addPanel(3, 'ContentManager/manage-books.php', '书籍', '管理书籍', 'administrator');
        Helper::addPanel(3, 'ContentManager/manage-movies.php', '电影', '管理电影', 'administrator');
        Helper::addPanel(3, 'ContentManager/manage-perfumes.php', '香水', '管理香水', 'administrator');
        Helper::addAction('movies-edit','ContentManager_Action');
        // 注册内容解析钩子
        Typecho_Plugin::factory('Widget_Abstract_Contents')->contentEx = array('ContentManager_Plugin', 'parseMovieShortcode');

        return _t('ContentManager 插件已激活');
    }

    public static function deactivate()
    {
        Helper::removePanel(3, 'ContentManager/manage-books.php');
        Helper::removePanel(3, 'ContentManager/manage-movies.php');
        Helper::removePanel(3, 'ContentManager/manage-perfumes.php');
        Helper::removeAction('movies-edit');
        // 取消内容解析钩子
        $plugin = Typecho_Plugin::factory('Widget_Abstract_Contents');
        if (is_array($plugin->contentEx)) {
            $contentEx = $plugin->contentEx;
            if (($key = array_search(array('ContentManager_Plugin', 'parseMovieShortcode'), $contentEx)) !== false) {
                unset($contentEx[$key]);
            }
            $plugin->contentEx = $contentEx;
        }
        return _t('ContentManager 插件已禁用');
    }

    public static function config(Typecho_Widget_Helper_Form $form)
    {
    }

    public static function personalConfig(Typecho_Widget_Helper_Form $form)
    {
    }

    /**
     * 电影短代码解析
     * @param $content
     * @param $widget
     * @param $last
     * @return array|string|string[]|null
     */
    public static function parseMovieShortcode($content, $widget, $last)
    {
        // 匹配短代码 [movie id=11]
        $pattern = '/\[movie id=(\d+)\]/';

        return preg_replace_callback($pattern, function($matches) {
            $movieId = $matches[1];
            $db = Typecho_Db::get();
            $prefix = $db->getPrefix();

            // 获取电影信息
            $query = $db->select()->from($prefix . 'movies')->where('id = ?', $movieId);
            $movie = $db->fetchRow($query);

            if ($movie) {
                // 返回电影信息的HTML
                return sprintf(
                    '<div class="movie-info">
                        <img src="%s" alt="%s" style="max-width: 100px;">
                        <h3>%s</h3>
                        <p><strong>导演：</strong>%s</p>
                        <p><strong>演员：</strong>%s</p>
                        <p><strong>分类：</strong>%s</p>
                        <p><strong>评分：</strong>%s</p>
                    </div>',
                    htmlspecialchars($movie['image_url']),
                    htmlspecialchars($movie['name']),
                    htmlspecialchars($movie['name']),
                    htmlspecialchars($movie['directors']),
                    htmlspecialchars($movie['actors']),
                    htmlspecialchars($movie['genres']),
                    htmlspecialchars($movie['rating'])
                );
            } else {
                return '<p>未找到电影信息</p>';
            }
        }, $content);
    }
}
