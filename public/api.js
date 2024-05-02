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

  /**
   * Информация об ошибке<br><br>
   * <a href="https://localhost:65443/api/v1_doc#error">Documentation</a>
   */
  static Error = class {
    /**
     * @type {number}
     */
    code;

    /**
     * @type {string|null}
     */
    inputField;

    /**
     * @type {Api.Phrase}
     */
    description;

    /**
     * @param {object} data
     */
    constructor(data) {
      this.code = data['code'];
      this.inputField = data['input_field'];
      this.description = new Api.Phrase(data['description']);
    }
  };

  /**
   * Товар<br><br>
   * <a href="https://localhost:65443/api/v1_doc#product">Documentation</a>
   */
  static Product = class {
    /**
     * ID
     * @type {number}
     */
    id;

    /**
     * Наименование
     * @type {string}
     */
    name;

    /**
     * Цена
     * @type {number}
     */
    price;

    /**
     * Флаг "Доступно для покупки"
     * @type {boolean}
     */
    available;

    /**
     * Дата создания
     * @type {Date}
     */
    createdAt;

    /**
     * @param {object} data
     */
    constructor(data) {
      this.id = data['id'];
      this.name = data['name'];
      this.price = data['price'];
      this.available = data['available'];
      this.createdAt = new Date(data['created_at'] * 1000);
    }
  };

  /**
   * Промокод<br><br>
   * <a href="https://localhost:65443/api/v1_doc#promocode">Documentation</a>
   */
  static Promocode = class {
    /**
     * ID
     * @type {number}
     */
    id;

    /**
     * Значение промокода
     * @type {string}
     */
    value;

    /**
     * Скидка в процентах
     * @type {number|null}
     */
    discountPercent;

    /**
     * Скидка в деньгах
     * @type {number|null}
     */
    discountValue;

    /**
     * Дата истечения промокода
     * @type {Date|null}
     */
    activeUntil;

    /**
     * Дата создания
     * @type {Date}
     */
    createdAt;

    /**
     * @param {object} data
     */
    constructor(data) {
      this.id = data['id'];
      this.value = data['value'];
      this.discountPercent = data['discount_percent'];
      this.discountValue = data['discount_value'];
      this.activeUntil = data['active_until'] === null ? null : new Date(data['active_until'] * 1000);
      this.createdAt = new Date(data['created_at'] * 1000);
    }
  };


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

  /**
   * ### Расчет стоимости товара
   *
   * - Error #1001: Произошла внутренняя ошибка сервера. Пожалуйста, повторите попытку через несколько секунд
   * - Error #2004: Товар не найден
   * - Error #2005: Некорректный формат налогового номера
   * - Error #2006: Купон не найден
   * - Error #2007: Купон не активен
   * - Error #2008: Товар не доступен
   * - Error #3002: Не выбран товар
   * - Error #3003: Не указан налоговый номер
   *
   * [Documentation](https://localhost:65443/api/v1_doc#calculatePrice)
   * @param {Object} param0
   * @param {number} param0.product ID товара
   * @param {string} param0.taxNumber Налоговый номер
   * @param {string|null} [param0.couponCode] Код купона
   * @throws {Api.ErrorsCollection}
   * @return {Promise<number>}
   */
  async calculatePrice({
    product,
    taxNumber,
    couponCode = null
  }) {
    return await this.request(
        'v1/calculatePrice',
        {
              product: product,
              taxNumber: taxNumber,
              couponCode: couponCode,
        },
    );
  }

  /**
   * ### Оплата покупки
   *
   * - Error #1001: Произошла внутренняя ошибка сервера. Пожалуйста, повторите попытку через несколько секунд
   * - Error #1003: Ошибка исполнения платежа
   * - Error #2004: Товар не найден
   * - Error #2005: Некорректный формат налогового номера
   * - Error #2006: Купон не найден
   * - Error #2007: Купон не активен
   * - Error #2008: Товар не доступен
   * - Error #2009: Обработчик платежей не найден
   * - Error #3002: Не выбран товар
   * - Error #3003: Не указан налоговый номер
   * - Error #3004: Не выбран обработчик платежа
   *
   * [Documentation](https://localhost:65443/api/v1_doc#purchase)
   * @param {Object} param0
   * @param {number} param0.product ID товара
   * @param {string} param0.taxNumber Налоговый номер
   * @param {string} param0.paymentProcessor Код обработчика платежа
   * @param {string|null} [param0.couponCode] Код купона
   * @throws {Api.ErrorsCollection}
   * @return {Promise<void>}
   */
  async purchase({
    product,
    taxNumber,
    paymentProcessor,
    couponCode = null
  }) {
    return await this.request(
        'v1/purchase',
        {
              product: product,
              taxNumber: taxNumber,
              couponCode: couponCode,
              paymentProcessor: paymentProcessor,
        },
    );
  }

}