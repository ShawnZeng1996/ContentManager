<?php

if (!defined('__TYPECHO_ROOT_DIR__')) exit;

/**
 * Typecho内容扩展插件，支持🎬电影、📚书籍、🛍️我的好物的管理，同时扩充文章类型支持💬说说。电影、书籍支持从豆瓣导入信息，本地化存储。
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

        // 检查是否已有书籍表
        $sql = "SHOW TABLES LIKE '{$prefix}books'";
        $result = $db->fetchRow($sql);
        if (!$result) {
            // 创建书籍表
            $sql = "CREATE TABLE IF NOT EXISTS `{$prefix}books` (
                `id` INT NOT NULL AUTO_INCREMENT,
                `title` VARCHAR(255) NOT NULL,
                `author` VARCHAR(255) NOT NULL,
                `publisher` VARCHAR(255) NOT NULL,
                `subtitle` VARCHAR(255),
                `origin_title` VARCHAR(255),
                `translator` VARCHAR(255),
                `pubdate` VARCHAR(255) NOT NULL,
                `cover_url` VARCHAR(255) NOT NULL,
                `douban_id` VARCHAR(255),
                `rating` FLOAT NOT NULL,
                PRIMARY KEY (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8;";
            $db->query($sql);
        }

        // 检查是否已有电影表
        $sql = "SHOW TABLES LIKE '{$prefix}movies'";
        $result = $db->fetchRow($sql);
        if (!$result) {
            // 创建电影表
            $sql = "CREATE TABLE IF NOT EXISTS `{$prefix}movies` (
                `id` INT NOT NULL AUTO_INCREMENT,
                `name` VARCHAR(255) NOT NULL,
                `directors` TEXT NOT NULL,
                `actors` TEXT NOT NULL,
                `genres` TEXT NOT NULL,
                `image_url` VARCHAR(255) NOT NULL,
                `douban_id` VARCHAR(255),
                `rating` FLOAT NOT NULL,
                PRIMARY KEY (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8;";
            $db->query($sql);
        }

        // 检查是否已有物品表
        $sql = "SHOW TABLES LIKE '{$prefix}goods'";
        $result = $db->fetchRow($sql);
        if (!$result) {
            // 创建物品表
            $sql = "CREATE TABLE IF NOT EXISTS `{$prefix}goods` (
                `id` INT NOT NULL AUTO_INCREMENT,
                `name` VARCHAR(255) NOT NULL,
                `brand` VARCHAR(255) NOT NULL,
                `category` VARCHAR(255) NOT NULL,
                `price` FLOAT,
                `specification` VARCHAR(255),
                `image_url` VARCHAR(255) NOT NULL,
                PRIMARY KEY (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8;";
            $db->query($sql);
        }

        // 检查是否已有post_type字段
        $sql = "SHOW COLUMNS FROM `{$prefix}contents` LIKE 'post_type'";
        $result = $db->fetchRow($sql);
        if (!$result) {
            // 添加post_type字段
            $sql = "ALTER TABLE `{$prefix}contents` ADD `post_type` VARCHAR(255) DEFAULT 'post'";
            $db->query($sql);
        }

        Helper::addPanel(3, 'ContentManager/manage-books.php', '书籍', '管理书籍', 'administrator');
        Helper::addPanel(3, 'ContentManager/manage-movies.php', '电影', '管理电影', 'administrator');
        Helper::addPanel(3, 'ContentManager/manage-goods.php', '好物', '管理我的好物', 'administrator');
        Helper::addAction('books-edit','ContentManager_Action');
        Helper::addAction('movies-edit','ContentManager_Action');
        Helper::addAction('goods-edit','ContentManager_Action');
        // 注册内容解析钩子
        Typecho_Plugin::factory('Widget_Abstract_Contents')->contentEx = array('ContentManager_Plugin', 'parseContentShortcode');
        // 在文章保存时触发
        Typecho_Plugin::factory('Widget_Contents_Post_Edit')->write = array('ContentManager_Plugin', 'savePostType');
        // 过滤文章内容
        Typecho_Plugin::factory('Widget_Abstract_Contents')->contentEx = array('ContentManager_Plugin', 'filterContent');
        // 添加钩子，修改文章编辑页面
        Typecho_Plugin::factory('admin/write-post.php')->option = array('ContentManager_Plugin', 'renderPostTypeSelect');

        return _t('ContentManager 插件已激活');
    }

    public static function deactivate()
    {
        Helper::removePanel(3, 'ContentManager/manage-books.php');
        Helper::removePanel(3, 'ContentManager/manage-movies.php');
        Helper::removePanel(3, 'ContentManager/manage-goods.php');
        Helper::removeAction('books-edit');
        Helper::removeAction('movies-edit');
        Helper::removeAction('goods-edit');
        return _t('ContentManager 插件已禁用');
    }

    public static function config(Typecho_Widget_Helper_Form $form)
    {
    }

    public static function personalConfig(Typecho_Widget_Helper_Form $form)
    {
    }

    /**
     * 加载 CSS 文件
     */
    public static function addCss()
    {
        $cssUrl = Helper::options()->pluginUrl . '/ContentManager/contentmanager.css';
        echo '<link rel="stylesheet" type="text/css" href="' . $cssUrl . '" />';
    }

    /**
     * 电影和书籍短代码解析
     * @param $content
     * @param $widget
     * @param $last
     * @return array|string|string[]|null
     */
    public static function parseContentShortcode($content, $widget, $last)
    {
        // 加载 CSS 文件
        self::addCss();

        // 匹配电影短代码 [movie id=1,2,3]
        $content = preg_replace_callback('/\[movie id=([\d,]+)\]/', function($matches) {
            $ids = explode(',', $matches[1]);
            $db = Typecho_Db::get();
            $prefix = $db->getPrefix();
            $html = '';

            foreach ($ids as $movieId) {
                $movieId = trim($movieId);

                // 获取电影信息
                $query = $db->select()->from($prefix . 'movies')->where('id = ?', $movieId);
                $movie = $db->fetchRow($query);

                if ($movie) {
                    // 将 rating 转换为浮点数
                    $rating = floatval($movie['rating']);
                    // 计算 stars-inner 的宽度
                    $ratingPercentage = ($rating / 10) * 100;

                    // 返回电影信息的HTML
                    $html .= sprintf(
                        '<div class="movie-item">
                    <img src="%s" alt="%s" class="movie-img" referrerpolicy="no-referrer" />
                    <div class="movie-info">
                        <h3 class="movie-name">%s</h3>
                        <span class="movie-directors"><strong>导演：</strong>%s</span>
                        <span class="movie-actors"><strong>演员：</strong>%s</span>
                        <span class="movie-genres"><strong>分类：</strong>%s</span>
                        <div class="movie-rating"><strong>评分：</strong>
                            <div class="rating">
                                <div class="stars-outer">
                                    <div class="stars-inner" style="width:%s%%;"></div>
                                </div>
                            </div>
                            %s
                        </div>
                    </div>
                </div>',
                        htmlspecialchars($movie['image_url']),
                        htmlspecialchars($movie['name']),
                        htmlspecialchars($movie['name']),
                        htmlspecialchars($movie['directors']),
                        htmlspecialchars($movie['actors']),
                        htmlspecialchars($movie['genres']),
                        htmlspecialchars($ratingPercentage),
                        htmlspecialchars($movie['rating'])
                    );
                } else {
                    $html .= '<p>未找到电影信息</p>';
                }
            }

            return $html;
        }, $content);

        // 匹配书籍短代码 [book id=1,2,3]
        $content = preg_replace_callback('/\[book id=([\d,]+)\]/', function($matches) {
            $ids = explode(',', $matches[1]);
            $db = Typecho_Db::get();
            $prefix = $db->getPrefix();
            $html = '';

            foreach ($ids as $bookId) {
                $bookId = trim($bookId);

                // 获取书籍信息
                $query = $db->select()->from($prefix . 'books')->where('id = ?', $bookId);
                $book = $db->fetchRow($query);

                if ($book) {
                    // 将 rating 转换为浮点数
                    $rating = floatval($book['rating']);
                    // 计算 stars-inner 的宽度
                    $ratingPercentage = ($rating / 10) * 100;

                    // 构建书籍信息的HTML
                    $subtitle = !empty($book['subtitle']) ? '<span class="book-subtitle"><strong>副标题：</strong>' . htmlspecialchars($book['subtitle']) . '</span>' : '';
                    $originTitle = !empty($book['origin_title']) ? '<span class="book-origin-title"><strong>原作名：</strong>' . htmlspecialchars($book['origin_title']) . '</span>' : '';
                    $translator = !empty($book['translator']) ? '<span class="book-translator"><strong>译者：</strong>' . htmlspecialchars($book['translator']) . '</span>' : '';

                    // 返回书籍信息的HTML
                    $html .= sprintf(
                        '<div class="book-item">
                    <img src="%s" alt="%s" class="book-img" referrerpolicy="no-referrer" />
                    <div class="book-info">
                        <h3 class="book-title">%s</h3>
                        <span class="book-author"><strong>作者：</strong>%s</span>
                        <span class="book-publisher"><strong>出版社：</strong>%s</span>
                        %s
                        %s
                        %s
                        <span class="book-pubdate"><strong>出版年：</strong>%s</span>
                        <div class="book-rating"><strong>评分：</strong>
                            <div class="rating">
                                <div class="stars-outer">
                                    <div class="stars-inner" style="width:%s%%;"></div>
                                </div>
                            </div>
                            %s
                        </div>
                    </div>
                </div>',
                        htmlspecialchars($book['cover_url']),
                        htmlspecialchars($book['title']),
                        htmlspecialchars($book['title']),
                        htmlspecialchars($book['author']),
                        htmlspecialchars($book['publisher']),
                        $subtitle,
                        $originTitle,
                        $translator,
                        htmlspecialchars($book['pubdate']),
                        htmlspecialchars($ratingPercentage),
                        htmlspecialchars($book['rating'])
                    );
                } else {
                    $html .= '<p>未找到书籍信息</p>';
                }
            }

            return $html;
        }, $content);

        // 匹配好物短代码 [good id=1,2,3] 和 [good list]
        $content = preg_replace_callback('/\[good(?: id=([\d,]+)| list)\]/', function($matches) {
            $db = Typecho_Db::get();
            $prefix = $db->getPrefix();
            $html = '';

            if (isset($matches[1])) {
                $ids = explode(',', $matches[1]);
                $html = '<div class="good-list">';
                foreach ($ids as $goodId) {
                    $goodId = trim($goodId);

                    // 获取好物信息
                    $query = $db->select()->from($prefix . 'goods')->where('id = ?', $goodId);
                    $good = $db->fetchRow($query);

                    if ($good) {
                        // 返回好物信息的HTML
                        $html .= sprintf(
                            '<div class="good-item">
                    <img src="%s" alt="%s" class="good-img" referrerpolicy="no-referrer" />
                    <div class="good-info">
                        <h3 class="good-name">%s</h3>
                        <span class="good-brand"><strong>品牌：</strong>%s</span>
                        <span class="good-category"><strong>分类：</strong>%s</span>
                        <span class="good-price"><strong>价格：</strong>%s</span>
                    </div>
                </div>',
                            htmlspecialchars($good['image_url']),
                            htmlspecialchars($good['name']),
                            htmlspecialchars($good['name']),
                            htmlspecialchars($good['brand']),
                            htmlspecialchars($good['category']),
                            htmlspecialchars($good['price'])
                        );
                    } else {
                        $html .= '<p>未找到好物信息</p>';
                    }
                }
            } else {
                // 获取所有好物信息
                $query = $db->select()->from($prefix . 'goods')->order($prefix . 'goods.id', Typecho_Db::SORT_ASC);
                $goods = $db->fetchAll($query);
                $html = '<div class="good-list">';

                foreach ($goods as $good) {
                    // 构建好物信息的HTML
                    $price = !empty($good['price']) ? '￥' . htmlspecialchars($good['price']) : '';
                    $specification = !empty($good['specification']) ? htmlspecialchars($good['specification']) : '';

                    // 如果价格和规格都存在，添加分隔符
                    if ($price && $specification) {
                        $specification = ' / ' . $specification;
                    }

                    // 返回好物信息的HTML
                    $html .= sprintf(
                        '<div class="good-item">
                                    <div class="good-img">
                                        <img src="%s" alt="%s" />
                                    </div>
                                    <div class="good-meta">
                                        <div class="good-brand good-category">%s · %s</div>
                                        <div class="good-name">%s
                                            <span class="good-price good-specification">%s%s</span>
                                        </div> 
                                    </div>
                                </div>',
                        htmlspecialchars($good['image_url']),
                        htmlspecialchars($good['name']),
                        htmlspecialchars($good['brand']),
                        htmlspecialchars($good['category']),
                        htmlspecialchars($good['name']),
                        $price,
                        $specification
                    );
                }

                $html .= '</div>';
            }

            return $html;
        }, $content);

        return $content;
    }

    public static function renderPostTypeSelect()
    {
        $options = [
            'post' => '文章',
            'shuoshuo' => '说说',
        ];

        $post_type = Typecho_Widget::widget('Widget_Contents_Post_Edit')->post_type ?? 'post';
        echo '<p class="description">';
        echo '<label for="post_type">' . _t('文章类型') . '</label>';
        echo '<select name="post_type" id="post_type" class="w-100">';
        foreach ($options as $value => $label) {
            $selected = $post_type === $value ? 'selected' : '';
            echo '<option value="' . $value . '" ' . $selected . '>' . $label . '</option>';
        }
        echo '</select>';
        echo '</p>';
    }

    public static function savePostType($contents, $class)
    {
        $request = Typecho_Request::getInstance();
        $post_type = $request->get('post_type', 'post');
        $contents['post_type'] = $post_type;
        return $contents;
    }

    public static function filterContent($content, $widget, $last)
    {
        if ($widget->post_type == 'shuoshuo') {
            // 根据需求展示说说的内容
            $content = '<div class="shuoshuo-content">' . $content . '</div>';
        }

        return $content;
    }

}
