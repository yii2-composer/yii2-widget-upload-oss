<?php
/**
 * Created by PhpStorm.
 * User: liyifei
 * Date: 2019/4/4
 * Time: 上午9:57
 */

// $id
// $inputId
// $options
// $signatureAction
// $isImage
// $ossHost
// $isMultiple
// $files
// contanerId
?>

    jQuery('#<?= $id ?>').fileupload(<?= json_encode($options) ?>);

    jQuery('#<?= $id ?>').on('fileuploadadd', function (e, data) {
        var that = $(this), container = that.parents("[id=<?= $containerId ?>]");
        $(".file-info", container).show().empty();
        $(".progress", container).show().attr("aria-valuenow", 0)
            .children().first().css("width", "0%")
            .html("0");
        $(".file-console", container).show().empty();
        var lastFile;
        $.each(data.files, function (index, file) {
            $(".file-info", container).html(file.name);
            lastFile = file;
        });
        fileUploadOSS.getSignature("<?= $signatureAction ?>", lastFile.name, data);
    });

    jQuery('#<?= $id ?>').on('fileuploadprogressall', function (e, data) {
        if (e.isDefaultPrevented()) {
            return false;
        }
        var that = $(this), container = that.parents("[id=<?= $containerId ?>]");
        var progress = Math.floor(data.loaded / data.total * 100);

        $(".progress", container).attr("aria-valuenow", progress)
            .children().first().css("width", progress + "%")
            .html(progress + "%");
        $(".progress", container).hide();
        $(".file-info", container).hide();
    });

    jQuery('#<?= $id ?>').on('fileuploadsubmit', function (e, data) {
        console.log(data);
        var that = $(this);
        data.url = fileUploadOSS.host;
        // data.formData = fileUploadOSS.formData;

        if (!data.url) {
            return false;
        }
    });

    jQuery('#<?= $id ?>').on('fileuploadfail', function (e, data) {
        var that = $(this), container = that.parents("[id=<?= $containerId ?>]");
        $(".file-console", container).empty().html("<span class=\"text-danger\">" + data.errorThrown + ": 请联系管理员!</span>");
    });

    jQuery('#<?= $id ?>').on('fileuploadprocessalways', function (e, data) {
        var that = $(this), container = that.parents("[id=<?= $containerId ?>]"),
            index = data.index, file = data.files[index];
        if (file.error) {
            $(".file-console", container).empty().html("<span class=\"text-danger\">" + file.error + "</span>");
        }
    });

    jQuery('#<?= $id ?>').on('fileuploaddone', function (e, data) {
        var isMultiple = <?= $isMultiple ?>;
        var that = $(this), container = that.parents("[id=<?= $containerId ?>]");
        var uimgs = container.find("#<?= $inputId ?>-uploaded-image-ul");
        var uploaded = $("#<?= $inputId ?>").val();

        if (isMultiple) {
            if (uploaded) {
                uploaded = uploaded.split(",");
            } else {
                uploaded = [];
            }
            uploaded.push(data.formData.key);
            $("#<?= $inputId ?>").val(uploaded.join(","));
        } else {
            $("#<?= $inputId ?>").val(data.formData.key);
        }

        <?php if($isImage): ?>
        var url = "<?= $ossHost ?>/" + data.formData.key;
        if (isMultiple) {
            uimgs.append("<li><img src=\"" + url + "\" width=\"100px\" height=\"100px\" /><a class=\"delete-uploaded\" data-target=\"<?= $inputId ?>\" data-key=\"" + data.formData.key + "\">删除</a></li>");
        } else {
            uimgs.html("<li><img src=\"" + url + "\" width=\"100px\" height=\"100px\" /><a class=\"delete-uploaded\" data-target=\"<?= $inputId ?>\" data-key=\"" + data.formData.key + "\">删除</a></li>");
        }
        <?php else: ?>
        var url = "<?= $ossHost ?>/" + data.formData.key;
        if (isMultiple) {
            uimgs.append("<li><a href=\"" + url + "\" target=\"_blank\">" + data.formData.key + "</a><a class=\"delete-uploaded\" data-target=\"<?= $inputId ?>\" data-key=\"" + data.formData.key + "\">删除</a></li>");
        } else {
            uimgs.html("<li><a href=\"" + url + "\" target=\"_blank\">" + data.formData.key + "</a><a class=\"delete-uploaded\" data-target=\"<?= $inputId ?>\" data-key=\"" + data.formData.key + "\">删除</a></li>");
        }
        <?php endif; ?>
    });

    <?php if($files): ?>
        var container = jQuery('#<?= $id ?>').parents("[id=<?= $containerId ?>]");
        var uimgs = container.find("#<?= $inputId ?>-uploaded-image-ul");
        <?php if (is_array($files)): ?>
            $("#<?= $inputId ?>").val("<?= implode(",", $files) ?>");
            <?php foreach($files as $file): ?>
                var url = "<?= "$ossHost/$file" ?>";
                <?php if($isImage): ?>
                    uimgs.append("<li><img src=\"" + url + "\" width=\"100px\" height=\"100px\" /><a class=\"delete-uploaded\" data-target=\"<?= $inputId ?>\" data-key=\"<?= $file ?>\">删除</a></li>");
                <?php else: ?>
                    uimgs.append("<li><a href=\"" + url + "\" target=\"_blank\"><?= $file ?></a><a class=\"delete-uploaded\" data-target=\"<?= $inputId ?>\" data-key=\"<?= $file ?>\">删除</a></li>");
                <?php endif; ?>
            <?php endforeach; ?>
        <?php else: ?>
            $("#<?= $inputId ?>").val("<?= $files ?>");
            var url = "<?= "$ossHost/$files" ?>";
            <?php if($isImage): ?>
                uimgs.append("<li><img src=\"" + url + "\" width=\"100px\" height=\"100px\" /><a class=\"delete-uploaded\" data-target=\"<?= $inputId ?>\" data-key=\"<?= $files ?>\">删除</a></li>");
            <?php else: ?>
                uimgs.append("<li><a href=\"" + url + "\" target=\"_blank\"><?= $files ?></a><a class=\"delete-uploaded\" data-target=\"<?= $inputId ?>\" data-key=\"<?= $files ?>\">删除</a></li>");
            <?php endif; ?>
        <?php endif; ?>
    <?php endif; ?>

    jQuery(document).on('click', '.delete-uploaded', function () {
        var isMultiple = <?= $isMultiple ?>;
        var targetId = $(this).attr('data-target');
        var targetValue = $("#" + targetId).val();
        var key = $(this).attr('data-key')

        if (isMultiple) {
            var targetValues = targetValue.split(",");
            var idx = targetValues.indexOf(key)
            if (idx !== -1) {
                targetValues.splice(idx, 1);
            }

            targetValue = targetValues.join(",")
        } else {
            targetValue = "";
        }

        $("#" + targetId).val(targetValue);
        $(this).parent().remove();
    });