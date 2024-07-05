<?php

class ContentManager_Action extends Typecho_Widget implements Widget_Interface_Do
{
    private $db;
    private $options;
    private $prefix;

    // 图像上传
    public function uploadImage()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['image_file'])) {
            $upload_dir = __TYPECHO_ROOT_DIR__ . '/usr/uploads/img/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }

            $file_name = basename($_FILES['image_file']['name']);
            $target_file = $upload_dir . $file_name;

            if (move_uploaded_file($_FILES['image_file']['tmp_name'], $target_file)) {
                chmod($target_file, 0666); // 设置文件权限为可读写
                $image_url = Typecho_Common::url('usr/uploads/img/' . $file_name, Helper::options()->siteUrl);
                echo $image_url; // 返回图像URL
                exit; // 终止脚本
            } else {
                echo 'Error uploading image';
                exit;
            }
        }
    }

    // 插入书籍信息
    public function insertBook()
    {
        $book = $this->request->from('title', 'author', 'publisher', 'subtitle', 'origin_title', 'translator', 'pubdate', 'cover_url', 'douban_id', 'rating');
        // 参数验证和过滤
        $book = array_map('trim', $book);
        $book['rating'] = (float)$book['rating'];
        try {
            $this->db->query($this->db->insert($this->prefix . 'books')->rows($book));
            $this->widget('Widget_Notice')->set(_t('书籍 %s 已经被增加', $book['title']), null, 'success');
        } catch (Exception $e) {
            $this->widget('Widget_Notice')->set(_t('书籍添加失败: %s', $e->getMessage()), null, 'error');
        }
        $this->response->redirect(Typecho_Common::url('extending.php?panel=ContentManager%2Fmanage-books.php', $this->options->adminUrl));
    }

    // 更新书籍信息
    public function updateBook()
    {
        $book = $this->request->from('id', 'title', 'author', 'publisher', 'subtitle', 'origin_title', 'translator', 'pubdate', 'cover_url', 'douban_id', 'rating');
        // 参数验证和过滤
        $book = array_map('trim', $book);
        $book['id'] = (int)$book['id'];
        $book['rating'] = (float)$book['rating'];
        try {
            $this->db->query($this->db->update($this->prefix . 'books')->rows($book)->where('id = ?', $book['id']));
            $this->widget('Widget_Notice')->set(_t('书籍 %s 已经被更新', $book['title']), null, 'success');
        } catch (Exception $e) {
            $this->widget('Widget_Notice')->set(_t('书籍更新失败: %s', $e->getMessage()), null, 'error');
        }
        $this->response->redirect(Typecho_Common::url('extending.php?panel=ContentManager%2Fmanage-books.php', $this->options->adminUrl));
    }

    // 删除书籍
    public function deleteBook()
    {
        $ids = $this->request->filter('int')->getArray('id');
        $deleteCount = 0;
        if ($ids && is_array($ids)) {
            foreach ($ids as $id) {
                try {
                    if ($this->db->query($this->db->delete($this->prefix . 'books')->where('id = ?', $id))) {
                        $deleteCount++;
                    }
                } catch (Exception $e) {
                    $this->widget('Widget_Notice')->set(_t('删除书籍 %d 失败: %s', $id, $e->getMessage()), null, 'error');
                }
            }
        }
        $this->widget('Widget_Notice')->set($deleteCount > 0 ? _t('书籍已经删除') : _t('没有书籍被删除'), null, $deleteCount > 0 ? 'success' : 'notice');
        $this->response->redirect(Typecho_Common::url('extending.php?panel=ContentManager%2Fmanage-books.php', $this->options->adminUrl));
    }

    // 插入电影信息
    public function insertMovie()
    {
        $movie = $this->request->from('name', 'douban_id', 'directors', 'actors', 'genres', 'image_url', 'rating');
        // 参数验证和过滤
        $movie = array_map('trim', $movie);
        $movie['rating'] = (float)$movie['rating'];
        try {
            $this->db->query($this->db->insert($this->prefix . 'movies')->rows($movie));
            $this->widget('Widget_Notice')->set(_t('电影 %s 已经被增加', $movie['name']), null, 'success');
        } catch (Exception $e) {
            $this->widget('Widget_Notice')->set(_t('电影添加失败: %s', $e->getMessage()), null, 'error');
        }
        $this->response->redirect(Typecho_Common::url('extending.php?panel=ContentManager%2Fmanage-movies.php', $this->options->adminUrl));
    }

    // 更新电影信息
    public function updateMovie()
    {
        $movie = $this->request->from('id', 'name', 'douban_id', 'directors', 'actors', 'genres', 'image_url', 'rating');
        // 参数验证和过滤
        $movie = array_map('trim', $movie);
        $movie['id'] = (int)$movie['id'];
        $movie['rating'] = (float)$movie['rating'];
        try {
            $this->db->query($this->db->update($this->prefix . 'movies')->rows($movie)->where('id = ?', $movie['id']));
            $this->widget('Widget_Notice')->set(_t('电影 %s 已经被更新', $movie['name']), null, 'success');
        } catch (Exception $e) {
            $this->widget('Widget_Notice')->set(_t('电影更新失败: %s', $e->getMessage()), null, 'error');
        }
        $this->response->redirect(Typecho_Common::url('extending.php?panel=ContentManager%2Fmanage-movies.php', $this->options->adminUrl));
    }

    // 删除电影
    public function deleteMovie()
    {
        $ids = $this->request->filter('int')->getArray('id');
        $deleteCount = 0;
        if ($ids && is_array($ids)) {
            foreach ($ids as $id) {
                try {
                    if ($this->db->query($this->db->delete($this->prefix . 'movies')->where('id = ?', $id))) {
                        $deleteCount++;
                    }
                } catch (Exception $e) {
                    $this->widget('Widget_Notice')->set(_t('删除电影 %d 失败: %s', $id, $e->getMessage()), null, 'error');
                }
            }
        }
        $this->widget('Widget_Notice')->set($deleteCount > 0 ? _t('电影已经删除') : _t('没有电影被删除'), null, $deleteCount > 0 ? 'success' : 'notice');
        $this->response->redirect(Typecho_Common::url('extending.php?panel=ContentManager%2Fmanage-movies.php', $this->options->adminUrl));
    }

    // 插入好物信息
    public function insertGood()
    {
        $good = $this->request->from('name', 'brand', 'category', 'price', 'specification', 'image_url');
        // 参数验证和过滤
        $good = array_map('trim', $good);

        if (!empty($good['price'])) {
            $good['price'] = (float)$good['price'];
        } else {
            $good['price'] = null;
        }

        try {
            $this->db->query($this->db->insert($this->prefix . 'goods')->rows($good));
            $this->widget('Widget_Notice')->set(_t('好物 %s 已经被增加', $good['name']), null, 'success');
        } catch (Exception $e) {
            $this->widget('Widget_Notice')->set(_t('好物添加失败: %s', $e->getMessage()), null, 'error');
        }
        $this->response->redirect(Typecho_Common::url('extending.php?panel=ContentManager%2Fmanage-goods.php', $this->options->adminUrl));
    }

    // 更新好物信息
    public function updateGood()
    {
        $good = $this->request->from('id', 'name', 'brand', 'category', 'price', 'specification', 'image_url');
        // 参数验证和过滤
        $good = array_map('trim', $good);
        $good['id'] = (int)$good['id'];
        $good['price'] = (float)$good['price'];
        try {
            $this->db->query($this->db->update($this->prefix . 'goods')->rows($good)->where('id = ?', $good['id']));
            $this->widget('Widget_Notice')->set(_t('好物 %s 已经被更新', $good['name']), null, 'success');
        } catch (Exception $e) {
            $this->widget('Widget_Notice')->set(_t('好物更新失败: %s', $e->getMessage()), null, 'error');
        }
        $this->response->redirect(Typecho_Common::url('extending.php?panel=ContentManager%2Fmanage-goods.php', $this->options->adminUrl));
    }

    // 删除好物信息
    public function deleteGood()
    {
        $ids = $this->request->filter('int')->getArray('id');
        $deleteCount = 0;
        if ($ids && is_array($ids)) {
            foreach ($ids as $id) {
                try {
                    if ($this->db->query($this->db->delete($this->prefix . 'goods')->where('id = ?', $id))) {
                        $deleteCount++;
                    }
                } catch (Exception $e) {
                    $this->widget('Widget_Notice')->set(_t('删除好物 %d 失败: %s', $id, $e->getMessage()), null, 'error');
                }
            }
        }
        $this->widget('Widget_Notice')->set($deleteCount > 0 ? _t('好物已经删除') : _t('没有好物被删除'), null, $deleteCount > 0 ? 'success' : 'notice');
        $this->response->redirect(Typecho_Common::url('extending.php?panel=ContentManager%2Fmanage-goods.php', $this->options->adminUrl));
    }


    public function action()
    {
        Helper::security()->protect();
        $user = Typecho_Widget::widget('Widget_User');
        $user->pass('administrator');
        $this->db = Typecho_Db::get();
        $this->prefix = $this->db->getPrefix();
        $this->options = Typecho_Widget::widget('Widget_Options');
        // 添加处理图像上传的逻辑
        $this->on($this->request->is('content-type=image&do=upload'))->uploadImage();
        // 处理书籍操作
        $this->on($this->request->is('content-type=book&do=insert'))->insertBook();
        $this->on($this->request->is('content-type=book&do=update'))->updateBook();
        $this->on($this->request->is('content-type=book&do=delete'))->deleteBook();
        // 处理电影操作
        $this->on($this->request->is('content-type=movie&do=insert'))->insertMovie();
        $this->on($this->request->is('content-type=movie&do=update'))->updateMovie();
        $this->on($this->request->is('content-type=movie&do=delete'))->deleteMovie();
        // 处理好物操作
        $this->on($this->request->is('content-type=good&do=insert'))->insertGood();
        $this->on($this->request->is('content-type=good&do=update'))->updateGood();
        $this->on($this->request->is('content-type=good&do=delete'))->deleteGood();

        $this->response->redirect($this->options->adminUrl);
    }
}
