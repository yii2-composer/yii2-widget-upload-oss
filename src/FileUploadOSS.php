<?php
/**
 * Created by PhpStorm.
 * User: guoxiaosong
 * Date: 2016/11/28
 * Time: 15:38
 */

namespace liyifei\uploadOSS;

use Yii;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;
use yii\helpers\Json;
use yii\helpers\Url;
use yii\web\JsExpression;
use yii\widgets\InputWidget;

class FileUploadOSS extends InputWidget
{
    public $ossHost;

    public $isImage = true;

    /**
     * @var string
     */
    public $signatureAction;

    public $multiple = false;

    /**
     * @var array the HTML attributes for the input tag.
     * @see \yii\helpers\Html::renderTagAttributes() for details on how attributes are being rendered.
     */
    public $containerOptions = [];
    /**
     * @var array the HTML attributes for the input tag.
     * @see \yii\helpers\Html::renderTagAttributes() for details on how attributes are being rendered.
     */
    public $uploadButtonOptions = [];
    /**
     * @var array the options for the underlying Bootstrap JS plugin.
     * Please refer to the corresponding Bootstrap plugin Web page for possible options.
     * For example, [this page](http://getbootstrap.com/javascript/#modals) shows
     * how to use the "Modal" plugin and the supported options (e.g. "remote").
     */
    public $clientOptions = [];
    /**
     * @var array the event handlers for the underlying Bootstrap JS plugin.
     * Please refer to the corresponding Bootstrap plugin Web page for possible events.
     * For example, [this page](http://getbootstrap.com/javascript/#modals) shows
     * how to use the "Modal" plugin and the supported events (e.g. "shown").
     */
    public $clientEvents = [];

    /**
     * @var string the template for rendering the input.
     */
    public $inputTemplate = <<< HTML
    <div class="file-info" style="display: none;"></div>
    <div class="progress" style="display: none;">
        <div class="progress-bar" role="progressbar" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100" style="width: 0;">
            0%
        </div>
    </div>
    <div class="file-console" style="display: none;"></div>
    <div class="input-group">
        {input}
        <span class="input-group-btn">
            {uploadButton}
        </span>
    </div>
    {uploadedImages}
HTML;

    public function init()
    {
        parent::init();
        if (!isset($this->options['class'])) {
            $this->options['class'] = 'form-control';
        }

        if (!isset($this->options['placeholder'])) {
            $this->options['placeholder'] = '点击右侧按钮上传文件，或者直接写入文件地址';
        }

        if (!isset($this->uploadButtonOptions['class'])) {
            $this->uploadButtonOptions['class'] = 'btn btn-primary fileinput-button';
        }

        if (!isset($this->containerOptions['id'])) {
            $this->containerOptions['id'] = $this->options['id'] . '-container';
        }

        $this->signatureAction = Url::to($this->signatureAction);
        $this->initClientOptions();

    }

    public function run()
    {
        $this->registerClientEvents();

        echo $this->renderInputGroup();
    }

    protected function getUploadInputId()
    {
        $id = $this->options['id'];

        return $id . '-upload-file';
    }

    protected function renderInputGroup()
    {
        $uploadButtonContent = ArrayHelper::remove($this->uploadButtonOptions, 'content', Yii::t('app', 'Select File'));


        $uploadButtonContent .= Html::input('file', 'file', '', ['id' => $this->getUploadInputId(), 'multiple' => $this->multiple]);

        $uploadButton = Html::tag('span', $uploadButtonContent, $this->uploadButtonOptions);

        if ($this->hasModel()) {
            $input = Html::activeHiddenInput($this->model, $this->attribute, $this->options);
        } else {
            $input = Html::hiddenInput($this->name, $this->value, $this->options);
        }

        if ($this->isImage) {
            $uploadedImages = '<div id="' . $this->options['id'] . '-uploaded-image-div"><ul id="' . $this->options['id'] . '-uploaded-image-ul" class="uploaded-image-ul clearfix"></ul></div>';
        } else {
            $uploadedImages = '<div id="' . $this->options['id'] . '-uploaded-image-div"><ul id="' . $this->options['id'] . '-uploaded-image-ul" class="uploaded-file-ul clearfix"></ul></div>';
        }

        $inputGroupContent = strtr($this->inputTemplate, [
            '{input}' => $input,
            '{uploadButton}' => $uploadButton,
            '{uploadedImages}' => $uploadedImages
        ]);
        return Html::tag('div', $inputGroupContent, $this->containerOptions);
    }

    protected function initClientOptions()
    {
        $clientOptions = [
            'autoUpload' => false,
//            'dataType' => 'json',
//            'acceptFileTypes' =>  new JsExpression('/(\.|\/)(gif|jpe?g|png)$/i'),
            "messages" => [
                "maxNumberOfFiles" => Yii::t('app', 'Maximum number of files exceeded'),
                "acceptFileTypes" => Yii::t('app', 'File type not allowed'),
                "maxFileSize" => Yii::t('app', 'File is too large'),
                "minFileSize" => Yii::t('app', 'File is too small')
            ],
            'formData' => [],
        ];
        $this->clientOptions = ArrayHelper::merge($clientOptions, $this->clientOptions);
    }

    protected function registerClientEvents()
    {
        $view = $this->getView();
        FileUploadAsset::register($view);
        FileUploadBaseAsset::register($view);

        // $js = [];
        $id = $this->getUploadInputId();

        $options = empty($this->clientOptions) ? '' : Json::htmlEncode($this->clientOptions);

        $files = null;
        $inputValue = $this->model[$this->attribute] ? $this->model[$this->attribute] : ArrayHelper::getValue($this->options, 'value', '');
        if ($this->multiple) {
            if ($inputValue) {
                $files = explode(',', $inputValue);
            }
        } else {
            $files = $inputValue;
        }

        $js = $this->renderFile($this->getViewPath() . '/uploader.php', [
            'id' => $id,
            'containerId' => $this->containerOptions['id'],
            'inputId' => $this->options['id'],
            'options' => $this->options,
            'signatureAction' => $this->signatureAction,
            'isImage' => $this->isImage,
            'ossHost' => $this->ossHost,
            'isMultiple' => $this->multiple ? 'true' : 'false',
            'files' => $files
        ]);
        $view->registerJs($js);
//        $js[] = "jQuery('#$id').fileupload($options);";
//
//        $clientEvents = [
//            'fileuploadadd' => new JsExpression('function(e, data) {
//                var that = $(this), container = that.parents("[id$=container]");
//                $(".file-info", container).show().empty();
//                $(".progress", container).show().attr("aria-valuenow", 0)
//                    .children().first().css("width", "0%")
//                    .html("0");
//                $(".file-console", container).show().empty();
//                var lastFile;
//                $.each(data.files, function (index, file) {
//                    $(".file-info", container).html(file.name);
//                    lastFile = file;
//                });
//                fileUploadOSS.getSignature("' . $this->signatureAction . '", lastFile.name);
//            }'),
//            'fileuploadprogressall' => new JsExpression('function(e, data) {
//                if (e.isDefaultPrevented()) {
//                    return false;
//                }
//                var that = $(this), container = that.parents("[id$=container]");
//                var progress = Math.floor(data.loaded / data.total * 100);
//
//                $(".progress", container).attr("aria-valuenow", progress)
//                    .children().first().css("width", progress + "%")
//                    .html(progress + "%");
//                $(".progress", container).hide();
//                $(".file-info", container).hide();
//            }'),
//            'fileuploadsubmit' => new JsExpression('function(e, data) {
//                var that = $(this);
//                data.url = fileUploadOSS.host;
//                data.formData = fileUploadOSS.formData;
//
//                if (!data.url) {
//                  return false;
//                }
//            }'),
//            'fileuploadfail' => new JsExpression('function(e, data) {
//                var that = $(this), container = that.parents("[id$=container]");
//                $(".file-console", container).empty().html("<span class=\"text-danger\">" + data.errorThrown + ": 请联系管理员!</span>");
//            }'),
//            'fileuploadprocessalways' => new JsExpression('function(e, data) {
//                var that = $(this), container = that.parents("[id$=container]"),
//                index = data.index, file = data.files[index];
//                if (file.error) {
//                    $(".file-console", container).empty().html("<span class=\"text-danger\">" + file.error + "</span>");
//                }
//            }'),
//            'fileuploaddone' => new JsExpression('function(e, data) {
//                var isMultiple = "' . $this->multiple . '";
//                var that = $(this), container = that.parents("[id$=container]");
//                var uimgs = container.find("#' . $this->options['id'] . '-uploaded-image-ul");
//                var uploaded = $("#' . $this->options['id'] . '").val();
//
//                if(isMultiple) {
//                    if (uploaded) {
//                        uploaded = uploaded.split(",");
//                    } else {
//                        uploaded = [];
//                    }
//                    uploaded.push(fileUploadOSS.formData.key);
//                    $("#' . $this->options['id'] . '").val(uploaded.join(","));
//                } else {
//                    $("#' . $this->options['id'] . '").val(fileUploadOSS.formData.key);
//                }
//
//                if( "' . $this->isImage . '" ) {
//                    var url = "' . $this->ossHost . '/" + fileUploadOSS.formData.key
//                    uimgs.append("<li><img src=\""+url+"\" width=\"100px\" height=\"100px\" /><a class=\"delete-uploaded\" data-target=\"' . $this->options['id'] . '\" data-key=\""+fileUploadOSS.formData.key+"\">删除</a></li>");
//                }
//            }'),
//        ];
//
//        if (!empty($clientEvents)) {
//            foreach ($clientEvents as $event => $handler) {
//                $js[] = "jQuery('#$id').on('$event', $handler);";
//            }
//        }
//
//        if (!empty($this->clientEvents)) {
//            foreach ($this->clientEvents as $event => $handler) {
//                $js[] = "jQuery('#$id').on('$event', $handler);";
//            }
//        }
//        $view->registerJs(implode("\n", $js));
    }
}