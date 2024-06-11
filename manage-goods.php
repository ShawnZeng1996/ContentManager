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
                    <li class="current"><a href="<?php $options->adminUrl('extending.php?panel=ContentManager/manage-goods.php'); ?>"><?php _e('好物管理'); ?></a></li>
                    <li><a href="https://shawnzeng.com/index.php/archives/3/" title="查看好物管理使用帮助" target="_blank"><?php _e('帮助'); ?></a></li>
                </ul>
            </div>

            <div class="col-mb-12 col-tb-9" role="main">
                <?php
                $prefix = $db->getPrefix();
                $goods = $db->fetchAll($db->select()->from($prefix . 'goods')->order($prefix . 'goods.id', Typecho_Db::SORT_ASC));
                ?>
                <form method="post" name="manage_goods" class="operate-form">
                    <div class="typecho-list-operate clearfix">
                        <div class="operate">
                            <label><i class="sr-only"><?php _e('全选'); ?></i><input type="checkbox" class="typecho-table-select-all" /></label>
                            <div class="btn-group btn-drop">
                                <button class="btn dropdown-toggle btn-s" type="button"><i class="sr-only"><?php _e('操作'); ?></i><?php _e('选中项'); ?> <i class="i-caret-down"></i></button>
                                <ul class="dropdown-menu">
                                    <li><a lang="<?php _e('你确定要删除这些好物吗'); ?>" href="<?php $security->index('/action/goods-edit?content-type=good&do=delete') ?>" ><?php _e('删除'); ?></a></li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <div class="typecho-table-wrap" style="padding: 20px 10px;">
                        <table class="typecho-list-table">
                            <colgroup>
                                <col width="20">
                                <col width="15%">
                                <col width="3%">
                                <col width="15%">
                                <col width="15%">
                                <col width="13%">
                                <col width="13%">
                                <col width="13%">
                                <col width="10%">
                            </colgroup>
                            <thead>
                            <tr>
                                <th></th>
                                <th><?php _e('图片'); ?></th>
                                <th><?php _e('id'); ?></th>
                                <th><?php _e('物品名称'); ?></th>
                                <th><?php _e('品牌'); ?></th>
                                <th><?php _e('分类'); ?></th>
                                <th><?php _e('价格'); ?></th>
                                <th><?php _e('规格'); ?></th>
                                <th><?php _e('操作'); ?></th>
                            </tr>
                            </thead>
                            <tbody>
                            <?php if (!empty($goods)): $alt = 0;?>
                                <?php foreach ($goods as $good): ?>
                                    <tr id="good-<?php echo $good['id']; ?>">
                                        <td><input type="checkbox" value="<?php echo $good['id']; ?>" name="id[]"/></td>
                                        <td><?php if ($good['image_url']) { ?>
                                                <img src="<?php echo $good['image_url']; ?>" style="max-width: 50px; max-height: 80px;"/>
                                            <?php } ?></td>
                                        <td><?php echo $good['id']; ?></td>
                                        <td><?php echo $good['name']; ?></td>
                                        <td><?php echo $good['brand']; ?></td>
                                        <td><?php echo $good['category']; ?></td>
                                        <td><?php if(!empty($good['price'])) echo '￥' . $good['price']; ?></td>
                                        <td><?php echo $good['specification']; ?></td>
                                        <td>
                                            <button type="button" class="btn primary" onclick="editGood(
                                                    '<?php echo $good['id']; ?>',
                                                    '<?php echo htmlspecialchars(addslashes($good['name'])); ?>',
                                                    '<?php echo htmlspecialchars(addslashes($good['brand'])); ?>',
                                                    '<?php echo htmlspecialchars(addslashes($good['category'])); ?>',
                                                    '<?php echo htmlspecialchars(addslashes($good['price'])); ?>',
                                                    '<?php echo htmlspecialchars(addslashes($good['specification'])); ?>',
                                                    '<?php echo htmlspecialchars(addslashes($good['image_url'])); ?>'
                                                    )"><?php _e('编辑'); ?></button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="8"><h6 class="typecho-list-table-title"><?php _e('没有任何好物'); ?></h6></td>
                                </tr>
                            <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </form>
            </div>

            <div class="col-mb-12 col-tb-3" role="form">
                <div class="typecho-mini-panel">
                    <form method="post" enctype="multipart/form-data" action="<?php $security->index('/action/goods-edit'); ?>">
                        <input type="hidden" name="content-type" id="content-type" value="good">
                        <input type="hidden" name="id" id="good-id">
                        <input type="hidden" name="do" id="action" value="insert">
                        <ul class="typecho-option">
                            <li>
                                <label for="name" class="typecho-label"><?php _e('物品名称*'); ?></label>
                                <input type="text" id="name" name="name" class="text" required />
                            </li>
                            <li>
                                <label for="brand" class="typecho-label"><?php _e('品牌*'); ?></label>
                                <input type="text" id="brand" name="brand" class="text" required />
                            </li>
                            <li>
                                <label for="category" class="typecho-label"><?php _e('分类*'); ?></label>
                                <input type="text" id="category" name="category" class="text" required />
                            </li>
                            <li>
                                <label for="price" class="typecho-label"><?php _e('价格（￥）'); ?></label>
                                <input type="text" id="price" name="price" class="text"/>
                            </li>
                            <li>
                                <label for="specification" class="typecho-label"><?php _e('规格'); ?></label>
                                <input type="text" id="specification" name="specification" class="text"/>
                            </li>
                            <li>
                                <label for="image_url" class="typecho-label"><?php _e('图片*'); ?></label>
                                <input type="text" id="image_url" name="image_url" class="text" required />
                                <input type="file" name="image_file" id="image_file" accept="image/*" onchange="uploadImage(this)" /> <!-- 添加 onchange 事件 -->
                                <img id="preview-image" src="" alt="Image Preview" style="width: 100px; display: none;">
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

    function editGood(id, name, brand, category, price, specification, image_url) {
        document.getElementById('good-id').value = id;
        document.getElementById('action').value = 'update';
        document.getElementById('name').value = name;
        document.getElementById('brand').value = brand;
        document.getElementById('category').value = category;
        document.getElementById('price').value = price;
        document.getElementById('specification').value = specification;
        document.getElementById('image_url').value = image_url;
        document.getElementById('preview-image').src = image_url;
        document.getElementById('preview-image').style.display = 'block';
    }

    function uploadImage(input) {
        var formData = new FormData();
        formData.append('image_file', input.files[0]);

        fetch('<?php echo $security->getIndex('action/goods-edit?content-type=image&do=upload'); ?>', { // 使用新的上传路径
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
</script>
<?php include 'footer.php'; ?>
