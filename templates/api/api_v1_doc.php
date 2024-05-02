<?php

// phpcs:ignoreFile

declare(strict_types=1);

/**
 * @var string $title
 * @var string $about_documentation
 * @var App\Localization\LocaleCode $language_code
 * @var App\Api\v1\ApiModel[]|string[] $models
 * @var string[] $models_errors
 * @var App\Api\v1\ApiMethod[]|string[] $methods
 * @var string[] $methods_errors
 * @var int[][] $methods_error_codes
 */

use App\Api\v1\Exceptions\ApiException;
use App\Api\v1\Output\ApiFieldType;

?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>
        <?= htmlspecialchars($title) ?>
    </title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="/api/v1_doc/css/bootstrap.min.css" rel="stylesheet">
    <link href="/api/v1_doc/css/style.css?<?= time() ?>" rel="stylesheet" media="screen">
</head>
<body class="preload">
<div class="dev_page_wrap">
    <div class="dev_page_head navbar navbar-static-top navbar-tg">
        <div class="navbar-inner">
            <div class="container clearfix">
                <ul class="nav navbar-nav">
                    <li><a href="/">Home</a></li>
                    <li class="active"><a href="#">API v1</a></li>
                </ul>
            </div>
        </div>
    </div>
    <div class="container clearfix">
        <div class="dev_page">
            <div id="dev_page_content_wrap" class=" ">
                <div id="dev_page_content">
                    <blockquote>
                        <p><?= $about_documentation ?></p>
                    </blockquote>

                    <?php foreach ($models_errors as $model_error) : ?>
                        <blockquote>
                            <p>Model error: <?= htmlspecialchars($model_error) ?></p>
                        </blockquote>
                    <?php endforeach; ?>

                    <?php foreach ($methods_errors as $method_error) : ?>
                        <blockquote>
                            <p>Method error: <?= htmlspecialchars($method_error) ?></p>
                        </blockquote>
                    <?php endforeach; ?>

                    <h3>
                        <a class="anchor" name="available-types" href="#available-types">
                            <i class="anchor-icon"></i>
                        </a>
                        Available types
                    </h3>
                    <?php foreach ($models as $model_name => $model_class) : ?>
                        <h4>
                            <a class="anchor" name="<?= strtolower($model_name) ?>" href="#<?= strtolower($model_name) ?>">
                                <i class="anchor-icon"></i>
                            </a>
                            <?= $model_name ?>
                        </h4>

                        <?php if ($model_class::getDescription() !== null) : ?>
                            <p>
                                <?= $model_class::getDescription() ?>
                            </p>
                        <?php endif; ?>

                        <?php $model_fields = $model_class::getFields(); ?>
                        <?php if ($model_fields !== []) : ?>
                            <table class="table">
                                <thead>
                                <tr>
                                    <th>
                                        Field
                                    </th>
                                    <th>
                                        Type
                                    </th>
                                    <th>
                                        Required
                                    </th>
                                    <th>
                                        Description
                                    </th>
                                </tr>
                                </thead>
                                <tbody>
                                <?php foreach ($model_fields as $model_field_name => $model_field) : ?>
                                    <tr>
                                        <td>
                                            <?= $model_field_name ?>
                                        </td>
                                        <td>
                                            <?php if ($model_field->type->isScalar()) : ?>
                                                <?= $model_field->type->name ?>
                                            <?php elseif ($model_field->type === ApiFieldType::Object) : ?>
                                                <?php $model_field_class_name = $model_field->model_class::getName(); ?>
                                                <a href="#<?= strtolower($model_field_class_name) ?>">
                                                    <?= $model_field_class_name ?>
                                                </a>
                                            <?php elseif ($model_field->type === ApiFieldType::Array) : ?>
                                                Array

                                                <?php if ($model_field->array_child_type !== null): ?>
                                                    of
                                                    <?php if ($model_field->array_child_type->isScalar()) : ?>
                                                        <?= $model_field->array_child_type->name ?>
                                                    <?php elseif (
                                                            $model_field->array_child_type === ApiFieldType::Object
    ) : ?>
                                                        <?php $model_field_class_name =
                                                            $model_field->array_child_model_class::getName(); ?>
                                                        <a href="#<?= strtolower($model_field_class_name) ?>">
                                                            <?= $model_field_class_name ?>
                                                        </a>
                                                    <?php endif; ?>
                                                <?php endif; ?>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($model_field->nullable) : ?>
                                            <?php else : ?>
                                                Required
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?= $model_field->description ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php endif; ?>
                    <?php endforeach; ?>
                    <h3>
                        <a class="anchor" name="available-methods" href="#available-methods">
                            <i class="anchor-icon"></i>
                        </a>
                        Available methods
                    </h3>
                    <?php foreach ($methods as $method_name => $method_class) : ?>
                        <?php $method_result_field = $method_class::getResultField(); ?>
                        <?php $method_parameters = array_merge(
                          $method_class::getAllParameters($language_code, true),
                          $method_class::getAllParameters($language_code, false),
                        ); ?>

                        <h4>
                            <a class="anchor" name="<?= str_replace('/', '_', $method_name) ?>"
                               href="#<?= str_replace('/', '_', $method_name) ?>">
                                <i class="anchor-icon"></i>
                            </a>
                            <?= $method_name ?><span1>:</span1>
                            <?php if ($method_result_field === null) : ?>
                                void
                            <?php else : ?>
                                <?php if ($method_result_field->type->isScalar()) : ?>
                                    <?= $method_result_field->type->name ?>
                                <?php elseif ($method_result_field->type === ApiFieldType::Object) : ?>
                                    <?php $model_result_field_class_name =
                                        $method_result_field->model_class::getName(); ?>
                                    <a href="#<?= strtolower($model_result_field_class_name) ?>">
                                        <?= $model_result_field_class_name ?></a>
                                <?php elseif ($method_result_field->type === ApiFieldType::Array) : ?>
                                    <?php if ($method_result_field->array_child_type->isScalar()) : ?>
                                        <?= $method_result_field->type->name ?>[]
                                    <?php elseif ($method_result_field->array_child_type === ApiFieldType::Object) : ?>
                                        <?php $model_result_field_class_name =
                                            $method_result_field->array_child_model_class::getName(); ?>
                                        <a href="#<?= strtolower($model_result_field_class_name) ?>">
                                            <?= $model_result_field_class_name ?></a>[]
                                    <?php endif; ?>
                                <?php endif; ?>
                                <?= $method_result_field->nullable ? '| null' : '' ?>
                            <?php endif; ?>

                            <small style="float: right;">
                                <a class="btn btn-sm btn-success" href="/api/v1/<?= $method_name ?>" target="_blank">
                                    Run
                                </a>
                            </small>
                        </h4>
                        <?php if ($method_class::getDescription() !== null) : ?>
                            <p>
                                <?= $method_class::getDescription() ?>
                            </p>
                        <?php endif; ?>

                        <?php $so = $method_class::getSelectionOptions($language_code);
                        if ($so !== null): ?>
                          Has pagination. Sort by variants: <?= implode(', ', $so->getSortByVariants()) ?>.
                          Default - <?= $so->getSortByDefault() ?>
                        <?php endif; ?>

                        <?php $allowed_groups = $method_class::getAllowedGroups(); ?>
                        <?php if ($allowed_groups !== null): ?>
                        <span style='color: red;'>
                          Allowed only for <?= ($allowed_groups === [] ? 'authorized users' : implode(', ', $allowed_groups)) ?>
                        </span><br>
                        <?php endif; ?>

                        <?php if ($method_parameters !== []) : ?>
                            <table class="table">
                                <thead>
                                <tr>
                                    <th>
                                        Parameter
                                    </th>
                                    <th>
                                        Type
                                    </th>
                                    <th>
                                        Required
                                    </th>
                                    <th>
                                        Description
                                    </th>
                                </tr>
                                </thead>
                                <tbody>
                                <?php foreach ($method_parameters as $method_parameter_name => $method_parameter) : ?>
                                    <tr>
                                        <td>
                                            <?= $method_parameter_name ?>
                                        </td>
                                        <td>
                                            <?= $method_parameter->type->value ?>
                                        </td>
                                        <td>
                                            <?php if ($method_parameter->required_and_empty_error === null) : ?>
                                            <?php else : ?>
                                                Required
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?= $method_parameter->description ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php endif; ?>

                        <?php if ($methods_error_codes[$method_name] !== []) : ?>
                            <table class="table">
                                <thead>
                                <tr>
                                    <th>
                                        Error
                                    </th>
                                    <th>
                                        Description
                                    </th>
                                </tr>
                                </thead>
                                <tbody>
                                <?php foreach ($methods_error_codes[$method_name] as $method_error_code) : ?>
                                    <tr>
                                        <td>
                                            <?= $method_error_code ?>
                                        </td>
                                        <td>
                                            <?= htmlspecialchars(ApiException::getDescriptionsByCode($method_error_code)['ru']) ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php endif; ?>

                      <div class='code-example'>
                        <pre>
import NewApi from '../../utils/new_api';

try {
  /**
   * @type {<?= $method_class::getResultField()?->getJsType('NewApi') ?? 'null' ?>}
   */
  let response = await NewApi.getInstance().<?= str_replace('/', '_', $method_name) ?>({
<?php foreach ($method_parameters as $method_parameter_name => $method_parameter) : ?>
    <?= $method_parameter_name ?>: <?= $method_parameter_name ?>, // <?= $method_parameter->getJsType() ?><?= $method_parameter->isRequired() ? '' : '|null' ?>

<?php endforeach; ?>
  });

  console.log(response);
} catch (exception) {
  if (exception instanceof NewApi.Error) {
<?php foreach ($methods_error_codes[$method_name] as $method_error_code) : ?>
    if (exception.hasCode(<?= $method_error_code ?>)) {
      // <?= htmlspecialchars(ApiException::getDescriptionsByCode($method_error_code)['ru']) ?>

    }
<?php endforeach; ?>
    console.log(exception);
  } else {
    console.log(exception);
  }
}</pre>
                      </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</div>
<script src="/api/v1_doc/js/main.js?46"></script>
<script src="/api/v1_doc/js/jquery.min.js?1"></script>
<script src="/api/v1_doc/js/bootstrap.min.js?1"></script>
<script>
    window.initDevPageNav && initDevPageNav();
    backToTopInit(<?= json_encode('To top', JSON_THROW_ON_ERROR)?>);
    removePreloadInit();
</script>
</body>
</html>