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
            //$sql = "ALTER TABLE `{$prefix}contents` ADD `post_type` VARCHAR(255) DEFAULT 'post'";
            $sql = "ALTER TABLE `{$prefix}contents` ADD `post_type` VARCHAR(255)";
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
        //Typecho_Plugin::factory('Widget_Abstract_Contents')->contentEx_10 = array('ContentManager_Plugin', 'filterContent');
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
                    $html .= self::buildMovieHtml($movie);
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
                    $html .= self::buildBookHtml($book);
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
                $html .= '<div class="good-list">';
                foreach ($ids as $goodId) {
                    $goodId = trim($goodId);

                    // 获取好物信息
                    $query = $db->select()->from($prefix . 'goods')->where('id = ?', $goodId);
                    $good = $db->fetchRow($query);

                    if ($good) {
                        $html .= self::buildGoodHtml($good);
                    } else {
                        $html .= '<p>未找到好物信息</p>';
                    }
                }
                $html .= '</div>';
            } else {
                // 获取所有好物信息
                $query = $db->select()->from($prefix . 'goods')->order($prefix . 'goods.id', Typecho_Db::SORT_ASC);
                $goods = $db->fetchAll($query);
                if (!empty($goods)) {
                    $html .= '<div class="good-list">';
                    foreach ($goods as $good) {
                        $html .= self::buildGoodHtml($good);
                    }
                    $html .= '</div>';
                } else {
                    $html .= '<p>未找到好物信息</p>';
                }
            }

            return $html;
        }, $content);

        return $content;
    }

    private static function buildMovieHtml($movie)
    {
        $rating = floatval($movie['rating']);
        $ratingPercentage = ($rating / 10) * 100;

        return sprintf(
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
    }

    private static function buildBookHtml($book)
    {
        $rating = floatval($book['rating']);
        $ratingPercentage = ($rating / 10) * 100;
        $subtitle = !empty($book['subtitle']) ? '<span class="book-subtitle"><strong>副标题：</strong>' . htmlspecialchars($book['subtitle']) . '</span>' : '';
        $originTitle = !empty($book['origin_title']) ? '<span class="book-origin-title"><strong>原作名：</strong>' . htmlspecialchars($book['origin_title']) . '</span>' : '';
        $translator = !empty($book['translator']) ? '<span class="book-translator"><strong>译者：</strong>' . htmlspecialchars($book['translator']) . '</span>' : '';

        return sprintf(
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
    }

    private static function buildGoodHtml($good)
    {
        $price = !empty($good['price']) ? '￥' . htmlspecialchars($good['price']) : '';
        $specification = !empty($good['specification']) ? htmlspecialchars($good['specification']) : '';
        if ($price && $specification) {
            $price = ' / ' . $price;
        }

        return sprintf(
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
            $specification,
            $price
        );
    }


    public static function renderPostTypeSelect()
    {
        $options = [
            'post' => '文章',
            'shuoshuo' => '说说',
        ];

        // 获取当前编辑的文章对象
        $widget = Typecho_Widget::widget('Widget_Contents_Post_Edit');
        $db = Typecho_Db::get();
        $prefix = $db->getPrefix();

        // 从数据库中获取当前文章的 post_type
        $postTypeQuery = $db->select('post_type')
            ->from($prefix . 'contents')
            ->where('cid = ?', $widget->cid);
        $post_type = $db->fetchRow($postTypeQuery)['post_type'] ?? 'post';


        echo '<section class="typecho-post-option">'.$post_type;
        echo '<label for="post_type" class="typecho-label">' . _t('文章类型') . '</label>';
        echo '<p><select name="post_type" id="post_type">';
        foreach ($options as $value => $label) {
            $selected = $post_type === $value ? 'selected' : '';
            echo '<option value="' . $value . '" ' . $selected . '>' . $label . '</option>';
        }
        echo '</select></p>';
        echo '</section>';
    }


    public static function savePostType($contents, $class)
    {
        $post_type = $class->request->get('post_type', 'post');
        $contents['post_type'] = $post_type;

        // 日志记录 'do' 和 'post_type'
        error_log('do: ' . $class->request->get('do'));
        error_log('post_type: ' . $post_type);

        // 数据库操作
        $db = Typecho_Db::get();
        $prefix = $db->getPrefix();

        // 获取文章ID
        $cid = $class->cid;

        // 如果没有文章ID，则表示是新增文章，需要在插入后更新 post_type 字段
        if (!$cid) {
            $cid = $class->insert($contents);
            $class->db->fetchRow(
                $class->select()->where('table.contents.cid = ?', $cid)->limit(1),
                [$class, 'push']
            );
        }

        if ($cid) {
            // 更新数据库中的 post_type 字段
            $db->query($db->update($prefix . 'contents')
                ->rows(array('post_type' => $post_type))
                ->where('cid = ?', $cid));
        }

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
