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
                    <li class="current"><a href="<?php $options->adminUrl('extending.php?panel=ContentManager/manage-books.php'); ?>"><?php _e('书籍管理'); ?></a></li>
                    <li><a href="https://shawnzeng.com/index.php/archives/3/" title="查看书籍管理使用帮助" target="_blank"><?php _e('帮助'); ?></a></li>
                </ul>
            </div>

            <div class="col-mb-12 col-tb-9" role="main">
                <?php
                $prefix = $db->getPrefix();
                $books = $db->fetchAll($db->select()->from($prefix . 'books')->order($prefix . 'books.id', Typecho_Db::SORT_ASC));
                ?>
                <form method="post" name="manage_books" class="operate-form">
                    <div class="typecho-list-operate clearfix">
                        <div class="operate">
                            <label><i class="sr-only"><?php _e('全选'); ?></i><input type="checkbox" class="typecho-table-select-all" /></label>
                            <div class="btn-group btn-drop">
                                <button class="btn dropdown-toggle btn-s" type="button"><i class="sr-only"><?php _e('操作'); ?></i><?php _e('选中项'); ?> <i class="i-caret-down"></i></button>
                                <ul class="dropdown-menu">
                                    <li><a lang="<?php _e('你确定要删除这些书籍吗'); ?>" href="<?php $security->index('/action/books-edit?content-type=book&do=delete') ?>" ><?php _e('删除'); ?></a></li>
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
                                <th><?php _e('封面'); ?></th>
                                <th><?php _e('书名|副标题'); ?></th>
                                <th><?php _e('id|豆瓣id'); ?></th>
                                <th><?php _e('作者|译者'); ?></th>
                                <th><?php _e('出版社|出版年'); ?></th>
                                <th><?php _e('原作名'); ?></th>
                                <th><?php _e('评分'); ?></th>
                                <th><?php _e('操作'); ?></th>
                            </tr>
                            </thead>
                            <tbody>
                            <?php if (!empty($books)): $alt = 0;?>
                                <?php foreach ($books as $book): ?>
                                    <tr id="book-<?php echo $book['id']; ?>">
                                        <td><input type="checkbox" value="<?php echo $book['id']; ?>" name="id[]"/></td>
                                        <td><?php if ($book['cover_url']) { ?>
                                                <img src="<?php echo $book['cover_url']; ?>" referrerpolicy="no-referrer" style="max-width: 50px; max-height: 80px;"/>
                                            <?php } ?></td>
                                        <td><?php echo $book['title']; echo $book['subtitle']; ?></td>
                                        <td><?php echo $book['id'] . ' | ' . $book['douban_id']; ?></td>
                                        <td><?php echo $book['author'] . ' | ' . $book['translator']; ?></td>
                                        <td><?php echo $book['publisher'] . ' | ' . $book['pubdate']; ?></td>
                                        <td><?php echo $book['origin_title']; ?></td>
                                        <td><?php echo $book['rating']; ?></td>
                                        <td>
                                            <button type="button" class="btn primary" onclick="editBook(
                                                    '<?php echo $book['id']; ?>',
                                                    '<?php echo htmlspecialchars(addslashes($book['title'])); ?>',
                                                    '<?php echo htmlspecialchars(addslashes($book['author'])); ?>',
                                                    '<?php echo htmlspecialchars(addslashes($book['publisher'])); ?>',
                                                    '<?php echo htmlspecialchars(addslashes($book['subtitle'])); ?>',
                                                    '<?php echo htmlspecialchars(addslashes($book['origin_title'])); ?>',
                                                    '<?php echo htmlspecialchars(addslashes($book['translator'])); ?>',
                                                    '<?php echo htmlspecialchars(addslashes($book['pubdate'])); ?>',
                                                    '<?php echo htmlspecialchars(addslashes($book['cover_url'])); ?>',
                                                    '<?php echo htmlspecialchars(addslashes($book['douban_id'])); ?>',
                                                    '<?php echo htmlspecialchars(addslashes($book['rating'])); ?>'
                                                    )"><?php _e('编辑'); ?></button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="9"><h6 class="typecho-list-table-title"><?php _e('没有任何书籍'); ?></h6></td>
                                </tr>
                            <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </form>
            </div>

            <div class="col-mb-12 col-tb-3" role="form">
                <div class="typecho-mini-panel">
                    <form method="post" enctype="multipart/form-data" action="<?php $security->index('/action/books-edit'); ?>">
                        <input type="hidden" name="content-type" id="content-type" value="book">
                        <input type="hidden" name="id" id="book-id">
                        <input type="hidden" name="do" id="action" value="insert">
                        <ul class="typecho-option">
                            <li>
                                <label for="douban_id" class="typecho-label"><?php _e('豆瓣ID'); ?></label>
                                <input type="text" id="douban_id" name="douban_id" class="text"/>
                                <button type="button" class="btn" onclick="fetchDoubanInfo()">获取书籍信息</button> <!-- 新增按钮 -->
                            </li>
                            <li>
                                <label for="title" class="typecho-label"><?php _e('书名*'); ?></label>
                                <input type="text" id="title" name="title" class="text" required />
                            </li>
                            <li>
                                <label for="author" class="typecho-label"><?php _e('作者*'); ?></label>
                                <input type="text" id="author" name="author" class="text" required />
                            </li>
                            <li>
                                <label for="publisher" class="typecho-label"><?php _e('出版社*'); ?></label>
                                <input type="text" id="publisher" name="publisher" class="text" required />
                            </li>
                            <li>
                                <label for="subtitle" class="typecho-label"><?php _e('副标题'); ?></label>
                                <input type="text" id="subtitle" name="subtitle" class="text" />
                            </li>
                            <li>
                                <label for="origin_title" class="typecho-label"><?php _e('原作名'); ?></label>
                                <input type="text" id="origin_title" name="origin_title" class="text" />
                            </li>
                            <li>
                                <label for="translator" class="typecho-label"><?php _e('译者'); ?></label>
                                <input type="text" id="translator" name="translator" class="text" />
                            </li>
                            <li>
                                <label for="pubdate" class="typecho-label"><?php _e('出版年*'); ?></label>
                                <input type="text" id="pubdate" name="pubdate" class="text" required />
                            </li>
                            <li>
                                <label for="cover_url" class="typecho-label"><?php _e('封面*'); ?></label>
                                <input type="text" id="cover_url" name="cover_url" class="text" required />
                                <input type="file" name="image_file" id="image_file" accept="image/*" onchange="uploadImage(this)" /> <!-- 添加 onchange 事件 -->
                                <img id="preview-image" src="" alt="Image Preview" style="width: 100px; display: none;">
                            </li>
                            <li>
                                <label for="rating" class="typecho-label"><?php _e('评分*'); ?></label>
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

    function editBook(id, title, author, publisher, subtitle, origin_title, translator, pubdate, cover_url, douban_id, rating) {
        document.getElementById('book-id').value = id;
        document.getElementById('action').value = 'update';
        document.getElementById('title').value = title;
        document.getElementById('author').value = author;
        document.getElementById('publisher').value = publisher;
        document.getElementById('subtitle').value = subtitle;
        document.getElementById('origin_title').value = origin_title;
        document.getElementById('translator').value = translator;
        document.getElementById('pubdate').value = pubdate;
        document.getElementById('cover_url').value = cover_url;
        document.getElementById('preview-image').src = cover_url;
        document.getElementById('preview-image').style.display = 'block';
        document.getElementById('douban_id').value = douban_id;
        document.getElementById('rating').value = rating;
    }

    function uploadImage(input) {
        var formData = new FormData();
        formData.append('image_file', input.files[0]);

        fetch('<?php echo $security->getIndex('action/books-edit?content-type=image&do=upload'); ?>', { // 使用新的上传路径
            method: 'POST',
            body: formData
        }).then(response => response.text()).then(data => {
            if (data.startsWith('http')) { // 检查返回的是否是URL
                document.getElementById('cover_url').value = data.trim();
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
        pluginUrl += '?info_type=book&book_id=' + doubanId;

        fetch(pluginUrl)
            .then(response => response.json())
            .then(data => {
                if (data.error) {
                    alert('获取书籍信息失败: ' + data.error);
                } else {
                    document.getElementById('title').value = data.title || '';
                    document.getElementById('author').value = data.author.join(' / ') || '';
                    document.getElementById('publisher').value = data.publisher || '';
                    document.getElementById('subtitle').value = data.subtitle || '';
                    document.getElementById('origin_title').value = data.origin_title || '';
                    document.getElementById('translator').value = data.translator.join(' / ') || '';
                    document.getElementById('pubdate').value = data.pubdate || '';
                    document.getElementById('cover_url').value = data.image || '';
                    document.getElementById('rating').value = data.rating.average || '';

                    // 更新图片预览
                    document.getElementById('preview-image').src = data.image || '';
                    document.getElementById('preview-image').style.display = data.image ? 'block' : 'none';
                }
            })
            .catch(error => {
                console.error('Error fetching book info:', error);
                alert('获取书籍信息失败');
            });
    }
</script>
<?php include 'footer.php'; ?>
