<?php

/** 初始化组件 */
Typecho_Widget::widget('Widget_Init');

/** 注册一个初始化插件 */
Typecho_Plugin::factory('admin/common.php')->begin();
Typecho_Widget::widget('Widget_Options')->to($options);
Typecho_Widget::widget('Widget_User')->to($user);
Typecho_Widget::widget('Widget_Security')->to($security);
Typecho_Widget::widget('Widget_Menu')->to($menu);

/** 初始化上下文 */
$request = $options->request;
$response = $options->response;
$db = Typecho_Db::get(); // 确保数据库对象的初始化

include 'header.php';
include 'menu.php';
?>

<div class="main">
    <div class="body container">
        <?php include 'page-title.php'; ?>
        <div class="row typecho-page-main manage-metas">
            <div class="col-mb-12">
                <ul class="typecho-option-tabs clearfix">
                    <li class="current"><a href="<?php $options->adminUrl('extending.php?panel=ContentManager/manage-movies.php'); ?>"><?php _e('电影管理'); ?></a></li>
                    <!--li><a href="<?php $options->adminUrl('options-plugin.php?config=ContentManager'); ?>"><?php _e('设置'); ?></a></li-->
                    <li><a href="https://example.com/help" title="查看电影管理使用帮助" target="_blank"><?php _e('帮助'); ?></a></li>
                </ul>
            </div>

            <div class="col-mb-12 col-tb-9" role="main">
                <?php
                $prefix = $db->getPrefix();
                $movies = $db->fetchAll($db->select()->from($prefix . 'movies')->order($prefix . 'movies.id', Typecho_Db::SORT_ASC));
                ?>
                <form method="post" name="manage_movies" class="operate-form">
                    <div class="typecho-list-operate clearfix">
                        <div class="operate">
                            <label><i class="sr-only"><?php _e('全选'); ?></i><input type="checkbox" class="typecho-table-select-all" /></label>
                            <div class="btn-group btn-drop">
                                <button class="btn dropdown-toggle btn-s" type="button"><i class="sr-only"><?php _e('操作'); ?></i><?php _e('选中项'); ?> <i class="i-caret-down"></i></button>
                                <ul class="dropdown-menu">
                                    <li><a lang="<?php _e('你确定要删除这些电影吗'); ?>" href="<?php $security->index('/action/movies-edit?content-type=movie&do=delete') ?>" ><?php _e('删除'); ?></a></li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <div class="typecho-table-wrap" style="padding: 20px 10px;">
                        <table class="typecho-list-table">
                            <colgroup>
                                <col width="20">
                                <col width="10%">
                                <col width="15%">
                                <col width="10%">
                                <col width="15%">
                                <col width="">
                                <col width="10%">
                                <col width="8%">
                                <col width="10%">
                            </colgroup>
                            <thead>
                            <tr>
                                <th></th>
                                <th><?php _e('海报'); ?></th>
                                <th><?php _e('电影名'); ?></th>
                                <th><?php _e('id|豆瓣id'); ?></th>
                                <th><?php _e('导演'); ?></th>
                                <th><?php _e('演员'); ?></th>
                                <th><?php _e('分类'); ?></th>
                                <th><?php _e('评分'); ?></th>
                                <th><?php _e('操作'); ?></th>
                            </tr>
                            </thead>
                            <tbody>
                            <?php if (!empty($movies)): $alt = 0;?>
                                <?php foreach ($movies as $movie): ?>
                                    <tr id="movie-<?php echo $movie['id']; ?>">
                                        <td><input type="checkbox" value="<?php echo $movie['id']; ?>" name="id[]"/></td>
                                        <td><?php if ($movie['image_url']) { ?>
                                                <img src="<?php echo $movie['image_url']; ?>" referrerpolicy="no-referrer" style="max-width: 50px; max-height: 80px;"/>
                                            <?php } ?></td>
                                        <td><?php echo $movie['name']; ?></td>
                                        <td><?php echo $movie['id'] . ' | ' . $movie['douban_id']; ?></td>
                                        <td><?php echo $movie['directors']; ?></td>
                                        <td><?php echo $movie['actors']; ?></td>
                                        <td><?php echo $movie['genres']; ?></td>
                                        <td><?php echo $movie['rating']; ?></td>
                                        <td>
                                            <button type="button" class="btn primary" onclick="editMovie(
                                                    '<?php echo $movie['id']; ?>',
                                                    '<?php echo htmlspecialchars(addslashes($movie['name'])); ?>',
                                                    '<?php echo htmlspecialchars(addslashes($movie['douban_id'])); ?>',
                                                    '<?php echo htmlspecialchars(addslashes($movie['directors'])); ?>',
                                                    '<?php echo htmlspecialchars(addslashes($movie['actors'])); ?>',
                                                    '<?php echo htmlspecialchars(addslashes($movie['genres'])); ?>',
                                                    '<?php echo htmlspecialchars(addslashes($movie['image_url'])); ?>',
                                                    '<?php echo htmlspecialchars(addslashes($movie['rating'])); ?>'
                                                    )"><?php _e('编辑'); ?></button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="8"><h6 class="typecho-list-table-title"><?php _e('没有任何电影'); ?></h6></td>
                                </tr>
                            <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </form>
            </div>

            <div class="col-mb-12 col-tb-3" role="form">
                <div class="typecho-mini-panel">
                    <form method="post" enctype="multipart/form-data" action="<?php $security->index('/action/movies-edit'); ?>">
                        <input type="hidden" name="content-type" id="content-type" value="movie">
                        <input type="hidden" name="id" id="movie-id">
                        <input type="hidden" name="do" id="action" value="insert">
                        <ul class="typecho-option">
                            <li>
                                <label for="douban_id" class="typecho-label"><?php _e('豆瓣ID'); ?></label>
                                <input type="text" id="douban_id" name="douban_id" class="text" required />
                                <button type="button" class="btn" onclick="fetchDoubanInfo()">获取电影信息</button> <!-- 新增按钮 -->
                            </li>
                            <li>
                                <label for="name" class="typecho-label"><?php _e('电影名'); ?></label>
                                <input type="text" id="name" name="name" class="text" required />
                            </li>
                            <li>
                                <label for="directors" class="typecho-label"><?php _e('导演'); ?></label>
                                <input type="text" id="directors" name="directors" class="text" required />
                            </li>
                            <li>
                                <label for="actors" class="typecho-label"><?php _e('演员'); ?></label>
                                <input type="text" id="actors" name="actors" class="text" required />
                            </li>
                            <li>
                                <label for="genres" class="typecho-label"><?php _e('分类'); ?></label>
                                <input type="text" id="genres" name="genres" class="text" required />
                            </li>
                            <li>
                                <label for="image_url" class="typecho-label"><?php _e('海报'); ?></label>
                                <input type="text" id="image_url" name="image_url" class="text" required />
                                <input type="file" name="image_file" id="image_file" accept="image/*" onchange="uploadImage(this)" /> <!-- 添加 onchange 事件 -->
                                <img id="preview-image" src="" alt="Image Preview" referrerpolicy="no-referrer" style="width: 100px; display: none;">
                            </li>
                            <li>
                                <label for="rating" class="typecho-label"><?php _e('评分'); ?></label>
                                <input type="number" step="0.1" id="rating" name="rating" class="text" style="background: #FFF;border: 1px solid #D9D9D6;padding: 7px;border-radius: 2px;box-sizing: border-box;" required />
                            </li>
                        </ul>
                        <button type="submit" class="btn primary"><?php _e('保存'); ?></button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
include 'copyright.php';
include 'common-js.php';
?>

<script type="text/javascript">
    (function () {
        $(document).ready(function () {
            $('.typecho-list-table').tableSelectable({
                checkEl: 'input[type=checkbox]',
                rowEl: 'tr',
                selectAllEl: '.typecho-table-select-all',
                actionEl: '.dropdown-menu a'
            });

            $('.btn-drop').dropdownMenu({
                btnEl: '.dropdown-toggle',
                menuEl: '.dropdown-menu'
            });

            <?php if (isset($request->id)): ?>
            $('.typecho-mini-panel').effect('highlight', '#AACB36');
            <?php endif; ?>
        });
    })();

    function editMovie(id, name, douban_id, directors, actors, genres, image_url, rating) {
        document.getElementById('movie-id').value = id;
        document.getElementById('action').value = 'update';
        document.getElementById('name').value = name;
        document.getElementById('douban_id').value = douban_id;
        document.getElementById('directors').value = directors;
        document.getElementById('actors').value = actors;
        document.getElementById('genres').value = genres;
        document.getElementById('image_url').value = image_url;
        document.getElementById('preview-image').src = image_url;
        document.getElementById('preview-image').style.display = 'block';
        document.getElementById('rating').value = rating;
    }

    function uploadImage(input) {
        var formData = new FormData();
        formData.append('image_file', input.files[0]);

        fetch('<?php echo $security->getIndex('action/movies-edit?content-type=image&do=upload'); ?>', { // 使用新的上传路径
            method: 'POST',
            body: formData
        }).then(response => response.text()).then(data => {
            if (data.startsWith('http')) { // 检查返回的是否是URL
                document.getElementById('image_url').value = data.trim();
                document.getElementById('preview-image').src = data.trim();
                document.getElementById('preview-image').style.display = 'block';
            } else {
                console.error('Error uploading image:', data);
            }
        }).catch(error => {
            console.error('Error uploading image:', error);
        });
    }

    function fetchDoubanInfo() {
        var doubanId = document.getElementById('douban_id').value;
        if (!doubanId) {
            alert('请填写豆瓣ID');
            return;
        }

        // 获取插件目录路径
        var pluginUrl = '<?php echo Typecho_Common::url('ContentManager/fetch-douban-info.php', Helper::options()->pluginUrl); ?>';
        pluginUrl += '?info_type=movie&movie_id=' + doubanId;

        fetch(pluginUrl)
            .then(response => response.json())
            .then(data => {
                if (data.error) {
                    alert('获取电影信息失败: ' + data.error);
                } else {
                    document.getElementById('name').value = data.title || '';
                    document.getElementById('directors').value = data.directors.map(d => d.name).join(' / ') || '';
                    document.getElementById('actors').value = data.casts.map(c => c.name).join(' / ') || '';
                    document.getElementById('genres').value = data.genres.join(' / ') || '';
                    document.getElementById('image_url').value = data.images.large || '';
                    document.getElementById('rating').value = data.rating.average || '';

                    // 更新图片预览
                    document.getElementById('preview-image').src = data.images.large || '';
                    document.getElementById('preview-image').style.display = data.images.large ? 'block' : 'none';
                }
            })
            .catch(error => {
                console.error('Error fetching movie info:', error);
                alert('获取电影信息失败');
            });
    }
</script>
<?php include 'footer.php'; ?>
