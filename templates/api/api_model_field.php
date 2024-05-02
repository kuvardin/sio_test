<?php

// phpcs:ignoreFile

declare(strict_types=1);

/**
 * @var string $name
 * @var ApiField $field
 */

use App\Api\v1\Output\ApiField;
use App\Api\v1\Output\ApiFieldType;
use App\Util\CaseSwitcher;

$nullable_string = $field->nullable ? " data['$name'] === null ? null :" : '';

?>
<?php if ($field->type === ApiFieldType::Timestamp) : ?>
this.<?= CaseSwitcher::snakeToCamel($name) ?> =<?= $nullable_string ?> new Date(data['<?= $name ?>'] * 1000);
<?php elseif ($field->type === ApiFieldType::Phrase) : ?>
this.<?= CaseSwitcher::snakeToCamel($name) ?> =<?= $nullable_string ?> new Api.Phrase(data['<?= $name ?>']);
<?php elseif ($field->type->isScalar()) : ?>
this.<?= CaseSwitcher::snakeToCamel($name) ?> = data['<?= $name ?>'];
<?php elseif ($field->type === ApiFieldType::Object) : ?>
this.<?= CaseSwitcher::snakeToCamel($name) ?> =<?= $nullable_string ?> new Api.<?= $field->model_class::getName() ?>(data['<?= $name ?>']);
<?php elseif ($field->type === ApiFieldType::Array && $field->array_child_type === null) : ?>
this.<?= CaseSwitcher::snakeToCamel($name) ?> =<?= $nullable_string ?> data['<?= $name ?>'];
<?php elseif ($field->type === ApiFieldType::Array) : ?>
<?php if ($field->array_child_type === ApiFieldType::Object) : ?>
data['<?= $name ?>'].forEach((<?= CaseSwitcher::snakeToCamel($name) ?>) => {
        this.<?= CaseSwitcher::snakeToCamel($name) ?>.push(new Api.<?= $field->array_child_model_class::getName() ?>(<?= CaseSwitcher::snakeToCamel($name) ?>));
      });
<?php elseif ($field->array_child_type === ApiFieldType::Phrase): ?>
this.<?= CaseSwitcher::snakeToCamel($name) ?> = data['<?= $name ?>'].map((phraseData) => new Api.Phrase(phraseData));
<?php else : ?>
this.<?= CaseSwitcher::snakeToCamel($name) ?> = data['<?= $name ?>'];
<?php endif; ?>
<?php endif; ?>
