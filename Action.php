<?php

class ContentManager_Action extends Typecho_Widget implements Widget_Interface_Do
{
    private $db;
    private $options;
    private $prefix;

    public function uploadImage()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['image_file'])) {
            $upload_dir = __TYPECHO_ROOT_DIR__ . '/usr/uploads/movies/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }

            $file_name = basename($_FILES['image_file']['name']);
            $target_file = $upload_dir . $file_name;

            if (move_uploaded_file($_FILES['image_file']['tmp_name'], $target_file)) {
                chmod($target_file, 0666); // 设置文件权限为可读写
                $image_url = Typecho_Common::url('usr/uploads/movies/' . $file_name, Helper::options()->siteUrl);
                echo $image_url; // 返回图像URL
                exit; // 终止脚本
            } else {
                echo 'Error uploading image';
                exit;
            }
        }
    }

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

    public function action()
    {
        Helper::security()->protect();
        $user = Typecho_Widget::widget('Widget_User');
        $user->pass('administrator');
        $this->db = Typecho_Db::get();
        $this->prefix = $this->db->getPrefix();
        $this->options = Typecho_Widget::widget('Widget_Options');
        $this->on($this->request->is('content-type=movie&do=insert'))->insertMovie();
        $this->on($this->request->is('content-type=movie&do=update'))->updateMovie();
        $this->on($this->request->is('content-type=movie&do=delete'))->deleteMovie();
        $this->on($this->request->is('content-type=image&do=upload'))->uploadImage(); // 添加处理图像上传的逻辑
        $this->response->redirect($this->options->adminUrl);
    }
}
