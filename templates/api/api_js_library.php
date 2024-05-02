<?php

// phpcs:ignoreFile

declare(strict_types=1);

use App\Api\v1\ApiModel;
use App\Api\v1\ApiMethod;
use App\Api\v1\Exceptions\ApiException;
use App\Api\v1\Input\ApiParameter;
use App\Api\v1\Output\ApiFieldType;
use App\Util\CaseSwitcher;
use App\Util\TemplatesEngine;
use App\Localization\LocaleCode;
use App\Api\v1\Models;

/**
 * @var string $title
 * @var LocaleCode $language_code
 * @var ApiModel[]|string[] $models
 * @var string[] $models_errors
 * @var ApiMethod[]|string[] $methods
 * @var string[] $methods_errors
 * @var int[][] $methods_error_codes
 */

?>
<script>
import axios from "axios";

export default class Api {
  static TRIGGER_ACTION_ORIENTATION_ROW = 'ROW';
  static TRIGGER_ACTION_ORIENTATION_STATEMENT = 'STATEMENT';

  static TRIGGER_ACTION_TIMING_BEFORE = 'BEFORE';
  static TRIGGER_ACTION_TIMING_AFTER = 'AFTER';
  static TRIGGER_ACTION_TIMING_INSTEAD_OF = 'INSTEAD OF';

  static TRIGGER_EVENT_MANIPULATION_INSERT = 'INSERT';
  static TRIGGER_EVENT_MANIPULATION_UPDATE = 'UPDATE';
  static TRIGGER_EVENT_MANIPULATION_DELETE = 'DELETE';
  static TRIGGER_EVENT_MANIPULATION_TRUNCATE = 'TRUNCATE';

  /**
   * @type {Api|null}
   */
  static _instance = null;

  /**
   * JWT access токен
   * @type {Api.AccessToken|null}
   * @private
   */
  _accessToken = null;

  /**
   * JWT refresh токен
   * @type {Api.AccessToken|null}
   * @private
   */
  _refreshToken = null;

  // noinspection JSValidateJSDoc
  /**
   * @type {AxiosInstance}
   * @private
   */
  _axiosInstance;

  /**
   * @param {Api.AccessToken|null} accessToken
   * @param {Api.AccessToken|null} refreshToken
   */
  constructor(accessToken = null, refreshToken = null) {
    this._accessToken = accessToken;
    this._refreshToken = refreshToken;
    this._axiosInstance = axios.create({
      baseURL: `${window.location.protocol}//${window.location.hostname}:${window.location.port}/`,
      responseType: "json",
      headers: {
        Accept: "application/json",
        "Content-Type": "application/json",
      },
    });
  };

  /**
   * @returns {Api}
   */
  static getInstance() {
    if (Api._instance === null) {
      Api._instance = new Api;
    }

    return Api._instance;
  }

  /**
   * @param {Api.AccessToken|null} accessToken
   * @param {boolean} writeToLocalStorage
   */
  setAccessToken(accessToken, writeToLocalStorage = false) {
    this._accessToken = accessToken;
    if (writeToLocalStorage) {
      localStorage.setItem(
        'access_token',
        accessToken === null
          ? null
          : JSON.stringify({ "token": accessToken.token, "expires_at": accessToken.expiresAt }),
      );
    }
  }

  /**
   * @param {Api.AccessToken|null} refreshToken
   * @param {boolean} writeToLocalStorage
   */
  setRefreshToken(refreshToken, writeToLocalStorage = false) {
    this._refreshToken = refreshToken;
    if (writeToLocalStorage) {
      localStorage.setItem(
        'refresh_token',
        refreshToken === null
          ? null
          : JSON.stringify({ "token": refreshToken.token, "expires_at": refreshToken.expiresAt }),
      );
    }
  }

  static Language = class {
    static CODE_RU = 'ru';
    static CODE_KK = 'kk';
    static CODE_EN = 'en';

    /**
     * @type {string[]}
     */
    static ALL = [this.CODE_RU, this.CODE_KK, this.CODE_EN];

    /**
     * Проверка кода языка на существование
     * @param {string} code
     * @return {boolean}
     */
    static checkCode(code) {
      return Api.Language.ALL.includes(code);
    }
  };

  static Action = class {
    static SHOW = 1;
    static CREATE = 2;
    static EDIT = 4;
    static DELETE = 8;

    static LIST_ALL = [
      this.SHOW,
      this.CREATE,
      this.EDIT,
      this.DELETE,
    ];

    static SUM_ALL = this.SHOW | this.CREATE | this.EDIT | this.DELETE;

    /**
     * Проверка прав доступа
     * @param {number} allowedActions Бинарная сумма разрешенных действий
     * @param {number} requiredActions Бинарная сумма требуемых дейстий
     * @param {boolean} requireAll Флаг "Требовать разрешение сразу всех действий"
     * @return boolean
     */
    static check(allowedActions, requiredActions, requireAll = true)
    {
      if (requireAll) {
        return (allowedActions & requiredActions) === requiredActions;
      }

      return (allowedActions & requiredActions) !== 0;
    }
  };

  static Phrase = class {
    /**
     * @type {Map<string,string|null>} Значение фразы на языках
     */
    values = new Map();

    constructor(data) {
      for (let langCode in data) {
        this.values.set(langCode, data[langCode]);
      }
    }

    /**
     * @return {boolean}
     */
    isEmpty() {
      for (let langCode in Object.fromEntries(this.values)) {
        if (this.values.get(langCode) !== null) {
          return false;
        }
      }

      return true;
    }

    /**
     * Затребовать фразу на указанном языке либо на любом другом
     * @param {string} langCode
     * @return string
     */
    require(langCode) {
      if (this.values.has(langCode) && this.values.get(langCode) !== null) {
        return this.values.get(langCode);
      }

      for (let langCode of this.values.keys()) {
        if (this.values.get(langCode) !== null) {
          return this.values.get(langCode);
        }
      }

      console.error('Phrase is empty ' + langCode, this.values);
      return 'EMPTY_PHRASE';
    }
  };

  /**
   * Полученные ошибки
   */
  static ErrorsCollection = class {
    /**
     * @type {Api.Error[]} Список ошибок
     */
    errors = [];

    /**
     * @type {number[]} Список кодов
     */
    codes = [];

    /**
     * @param {Api.Error} error
     */
    addError(error) {
      this.errors.push(error);
      this.codes.push(error.code);
    }

    /**
     * Поиск кода ошибки в списке полученных
     * @param {number} codes
     * @return {boolean}
     */
    hasCode(...codes) {
      for (code of codes) {
        if (this.codes.includes(code)) {
          return true;
        }
      }

      return false;
    }
  };

<?php foreach ($models as $model): ?>
  /**
   * <?= $model::getDescription() ?? "Class {$model::getName()}" ?><br><br>
   * <a href="https://localhost:65443/api/v1_doc#<?= strtolower($model::getName()) ?>">Documentation</a>
   */
  static <?= $model::getName() ?> = class {
<?php foreach ($model::getFields() as $field_name => $field): ?>
    /**
<?php if ($field->description !== null): ?>
     * <?= $field->description ?>

<?php endif; ?>
     * @type {<?= $field->getJsType() ?><?= ($field->nullable ? '|null' : '') ?>}
     */
    <?= CaseSwitcher::snakeToCamel($field_name) ?><?= $field->type === ApiFieldType::Array ? ' = []' : '' ?>;

<?php endforeach; ?>
    /**
     * @param {object} data
     */
    constructor(data) {
<?php foreach ($model::getFields() as $field_name => $field): ?>
      <?= TemplatesEngine::render(ROOT_DIR . '/templates/api/api_model_field.php', [
          'field' => $field,
          'name' => $field_name,
      ]) ?>
<?php endforeach; ?>
    }
<?php if ($model === Models\GroupsPermissionsApiModel::class): ?>

    /**
     * Получить разрешенные действия группы
     * @param {string} groupCode
     * @return number
     */
    getAllowedActionsForGroup(groupCode) {
      for (let groupAllowedActions of this.allowedActionsPerGroups) {
        if (groupAllowedActions.groupCode === groupCode) {
          return groupAllowedActions.allowedActions;
        }
      }

      return this.permissionsDefault;
    }

    /**
     * Получить бинарную сумму разрешенных действий для переданных кодов групп
     * @param {string[]} groupCodes
     * @return number
     */
    getAllowedActionsForGroups(groupCodes) {
      let result = 0;

      groupCodes.forEach(
        (groupCode) => {
          result = (result | this.getAllowedActionsForGroup(groupCode));
        }
      )

      return result;
    }

    /**
     * Проверка наличия прав
     * @param {string[]} groupCodes
     * @param {number} requiredActions
     * @param {boolean} requireAll
     * @return {boolean}
     */
    checkPermissionsForGroups(groupCodes, requiredActions, requireAll = true) {
      let allowedActions = this.getAllowedActionsForGroups(groupCodes);
      return Api.Action.check(allowedActions, requiredActions, requireAll);
    }
<?php elseif ($model === App\Api\v1\Models\SelectionDataApiModel::class): ?>

    /**
     * Получить данные о выборки для следующей страницы
     * @return {Api.SelectionData|null}
     */
    getNext() {
      if (this.pagesTotal < this.page) {
        return new Api.SelectionData({
          'limit': this.limit,
          'total_amount': this.totalAmount,
          'page': this.page + 1,
          'pages_total': this.pagesTotal,
          'sort_by': this.sortBy,
          'sort_direction': this.sortDirection,
          'sort_variants': this.sortVariants,
        });
      }

      return null;
    }
<?php endif; ?>
  };

<?php endforeach; ?>

  /**
   * Отправка запроса к API
   * @param {string} method Метод API
   * @param {object} data Данные
   * @param {function|null} before Callback-функция для преобработки данных ответа
   * @param {boolean} allowRecursive Защита от бесконечной рекурсии
   * @throws {Api.ErrorsCollection}
   * @return {Promise}
   */
  async request(method, data, before = null, allowRecursive = true) {
    const url = '/api/' + method;

    if (this._accessToken !== null && method !== 'v1/refreshToken') {
      if ((this._accessToken.expiresAt - 100000) < Math.floor(Date.now() / 1000)) {
        const newTokensPair = await this.refreshToken({token: this._refreshToken.token});
        this.setAccessToken(newTokensPair.accessToken, true);
        this.setRefreshToken(newTokensPair.refreshToken, true);
        console.log('Access tokens updated automatically ;)');
      }

      this._axiosInstance.defaults.headers.Authorization = `Bearer ${this._accessToken.token}`;
    } else if (this._axiosInstance.defaults.headers.hasOwnProperty('Authorization')) {
      delete this._axiosInstance.defaults.headers.Authorization;
    }

    try {
      let response = await this._axiosInstance.post(url, data);

      const result = response.data['result'];
      const apiErrorsData = response.data['errors'];

      if (apiErrorsData.length) {
        let errorsCollection = new Api.ErrorsCollection;

        apiErrorsData.forEach((apiErrorData) => {
          errorsCollection.addError(new Api.Error(apiErrorData));
        });

        throw errorsCollection;
      }

      if (before !== null) {
        return before(result);
      }

      return result;
    } catch (e) {
      if (e?.response?.status === 401) {
        const newTokensPair = await this.refreshToken({token: this._refreshToken.token});
        this.setAccessToken(newTokensPair.accessToken, true);
        this.setRefreshToken(newTokensPair.refreshToken, true);
        console.log('Access tokens updated automatically after error ;)');
        return this.request(method, data, before, false);
      } else {
        throw e;
      }
    }
  };

<?php foreach ($methods as $method_name => $method):
    $parameters_names_sc_required = array_map(
      function (string $name) {
        return CaseSwitcher::snakeToCamel($name);
      },
      array_keys($method::getAllParameters($language_code, true)),
    );

    $parameters_names_sc_not_required = array_map(
      function (string $name) {
        return CaseSwitcher::snakeToCamel($name) . ' = null';
      },
      array_keys($method::getAllParameters($language_code, false)),
    );

    $all_parameters = array_merge($parameters_names_sc_required, $parameters_names_sc_not_required);
    $so = $method::getSelectionOptions($language_code);

    $all_parameters_obj = array_merge(
        $method::getAllParameters($language_code, true),
        $method::getAllParameters($language_code, false),
    );
?>
  /**
   * ### <?= $method::getDescription() ?? "Method $method_name" ?>

   *
<?php if (!empty($methods_error_codes[$method_name])): ?>
<?php foreach (($methods_error_codes[$method_name] ?? []) as $error_code): ?>
   * - Error #<?= $error_code ?>: <?= htmlspecialchars(ApiException::getDescriptionsByCode($error_code)['ru']) ?>

<?php endforeach; ?>
   *
<?php endif; ?>
<?php if ($so !== null): ?>
   * Sort by variants: <?= implode(', ', $so->getSortByVariants()) ?>

   *
   * Sort by default: <?= $so->getSortByDefault() ?>

   *
<?php endif; ?>
   * [Documentation](https://localhost:65443/api/v1_doc#<?= str_replace('/', '_', $method_name) ?>)
<?php if ($all_parameters_obj !== []): ?>
   * @param {Object} param0
<?php foreach ($all_parameters_obj as $parameter_name => $parameter): ?>
   * @param {<?= $parameter->getJsType() ?><?= $parameter->isRequired() ? '' : '|null' ?>} <?= $parameter->isRequired() ? '' : '[' ?>param0.<?= CaseSwitcher::snakeToCamel($parameter_name) ?><?= $parameter->isRequired() ? '' : ']' ?> <?= $parameter->description ?>

<?php endforeach; ?>
<?php endif; ?>
   * @throws {Api.ErrorsCollection}
   * @return {Promise<<?= $method::getResultField()?->getJsType() ?? 'void' ?>>}
   */
  async <?= str_replace('/', '_', $method_name) ?>({
    <?= implode(",\n    ", $all_parameters) ?>

  }) {
    return await this.request(
        'v1/<?= $method_name ?>',
        {
<?php foreach ($method::getAllParameters($language_code) as $parameter_name => $parameter): ?>
          <?= TemplatesEngine::render(ROOT_DIR . '/templates/api/api_request_parameter.php', [
  'parameter' => $parameter,
  'name' => $parameter_name,
]) ?>
<?php endforeach; ?>
        },
<?php if ($method::getResultField() !== null): ?>
<?php if ($method::getResultField()->type === ApiFieldType::Array && $method::getResultField()->array_child_type === ApiFieldType::Object): ?>
        (response => response.map(item => new Api.<?= $method::getResultField()->array_child_model_class::getName() ?>(item)))
<?php elseif ($method::getResultField()->type === ApiFieldType::Object): ?>
        (response => new Api.<?= $method::getResultField()->model_class::getName() ?>(response))
<?php elseif ($method::getResultField()->type === ApiFieldType::Phrase): ?>
        (response => new Api.Phrase(response))
<?php endif; ?>
<?php endif; ?>
    );
  }

<?php endforeach; ?>
}

</script>