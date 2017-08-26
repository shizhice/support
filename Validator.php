<?php
/**
 * Created by PhpStorm.
 * User: shizhice
 * Date: 2017/8/26
 * Time: 下午1:41
 */

namespace Shizhice\Support;


class Validator
{
    private static $instance;
    protected $data = [];
    protected $rules = [];
    protected $regex = [];
    protected $errorMessages = [];
    protected $customMessages = [];
    protected static $customRuleValidate = [];

    /**
     * Validator constructor.
     * @param array $rules
     * @param array $message
     */
    public function __construct(array $rules = [], $message = [])
    {
        $this->customMessages = $message;
        $this->setRule($rules);
    }

    /**
     * 设置验证规则
     * @param $rules
     */
    private function setRule($rules)
    {
        $this->rules = array_merge_recursive(
            $this->rules, $this->explodeExplicitRule($rules)->rules
        );
    }

    /**
     * 分割验证规则
     * @param $rules
     * @return object
     */
    private function explodeExplicitRule($rules)
    {
        foreach ($rules as $key => &$rule) {
            if (is_array($rule)) {

            }elseif($rule instanceof \Closure){
                $rule = [$rule];
            }else{
                $rule = explode("|", $rule);
            }
        }
        return (object) [
            'rules' => $rules
        ];
    }

    /**
     * 实例化验证
     * @param array $rules
     * @param array $message
     * @return Validator
     */
    public static function make($rules = [], $message = [])
    {
        if (is_null(self::$instance)) {
            self::$instance = new self($rules, $message);
        }
        return self::$instance;
    }

    public function pass(array $data)
    {
        $this->data = $data;
        foreach ($this->rules as $attribute => $rules) {

            $value = isset($this->data[$attribute]) ? $this->data[$attribute] : '';
            // 存在验证规则required 或者 value 存在的时候进行验证
            if (!empty($value) || 0 === $value || in_array('require', $rules)) {
                foreach ($rules as $rule) {
                    if (! $this->checkItem($value, $rule)) {
                        $currentRule = explode(":", $rule)[0];
                        $msgKey = sprintf("%s.%s", $attribute, $currentRule);
                        $this->errorMessages[$attribute][$currentRule] = isset($this->customMessages[$msgKey])
                            ? $this->customMessages[$msgKey]
                            : sprintf("%s 验证失败", $msgKey);
                    }
                }
            }
        }

        return empty($this->errorMessages);
    }

    protected function checkItem($value, $rule)
    {
        if ($rule instanceof \Closure) {
            return $rule($value);
        }else {
            @list($rule, $args) = explode(":", $rule);
            $args and $args = explode(",", $args);

            if (method_exists($this, $rule.'Validate')) {
                return $this->{$rule.'Validate'}($value,  ...$args);
            }else{
                return $this->is($value, $rule);
            }
        }
    }

    protected function is($value, $rule)
    {
        switch ($rule) {
            case 'require':
                // 必须
                $result = !empty($value) || '0' == $value;
                break;
            case 'date':
                // 是否是一个有效日期
                $result = false !== strtotime($value);
                break;
            case 'alpha':
                // 只允许字母
                $result = $this->regex($value, '/^[A-Za-z]+$/');
                break;
            case 'alphaNum':
                // 只允许字母和数字
                $result = $this->regex($value, '/^[A-Za-z0-9]+$/');
                break;
            case 'alphaDash':
                // 只允许字母、数字和下划线 破折号
                $result = $this->regex($value, '/^[A-Za-z0-9\-\_]+$/');
                break;
            case 'chs':
                // 只允许汉字
                $result = $this->regex($value, '/^[\x{4e00}-\x{9fa5}]+$/u');
                break;
            case 'chsAlpha':
                // 只允许汉字、字母
                $result = $this->regex($value, '/^[\x{4e00}-\x{9fa5}a-zA-Z]+$/u');
                break;
            case 'chsAlphaNum':
                // 只允许汉字、字母和数字
                $result = $this->regex($value, '/^[\x{4e00}-\x{9fa5}a-zA-Z0-9]+$/u');
                break;
            case 'chsDash':
                // 只允许汉字、字母、数字和下划线_及破折号-
                $result = $this->regex($value, '/^[\x{4e00}-\x{9fa5}a-zA-Z0-9\_\-]+$/u');
                break;
            case 'activeUrl':
                // 是否为有效的网址
                $result = checkdnsrr($value);
                break;
            case 'ip':
                // 是否为IP地址
                $result = $this->filter($value, [FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 | FILTER_FLAG_IPV6]);
                break;
            case 'url':
                // 是否为一个URL地址
                $result = $this->filter($value, FILTER_VALIDATE_URL);
                break;
            case 'float':
                // 是否为float
                $result = $this->filter($value, FILTER_VALIDATE_FLOAT);
                break;
            case 'number':
                $result = is_numeric($value);
                break;
            case 'integer':
                // 是否为整型
                $result = $this->filter($value, FILTER_VALIDATE_INT);
                break;
            case 'email':
                // 是否为邮箱地址
                $result = $this->filter($value, FILTER_VALIDATE_EMAIL);
                break;
            case 'boolean':
                // 是否为布尔值
                $result = in_array($value, [true, false, 0, 1, '0', '1'], true);
                break;
            case 'array':
                // 是否为数组
                $result = is_array($value);
                break;
            default:
                if (isset(self::$customRuleValidate[$rule])) {
                    // 注册的验证规则
                    $result = call_user_func_array(self::$customRuleValidate[$rule], [$value]);
                } else {
                    // 正则验证
                    $result = $this->regex($value, $rule);
                }
        }
        return $result;
    }
    /**
     * 使用filter_var方式验证
     * @param $value
     * @param $rule
     * @return bool
     */
    protected function filter($value, $rule)
    {
        if (is_string($rule) && strpos($rule, ',')) {
            list($rule, $param) = explode(',', $rule);
        } elseif (is_array($rule)) {
            $param = isset($rule[1]) ? $rule[1] : null;
            $rule  = $rule[0];
        } else {
            $param = null;
        }
        return false !== filter_var($value, is_int($rule) ? $rule : filter_id($rule), $param);
    }

    /**
     * 使用正则验证数据
     * @access protected
     * @param mixed     $value  字段值
     * @param mixed     $rule  验证规则 正则规则或者预定义正则名
     * @return mixed
     */
    protected function regex($value, $rule)
    {
        if (isset($this->regex[$rule])) {
            $rule = $this->regex[$rule];
        }
        if (0 !== strpos($rule, '/') && !preg_match('/\/[imsU]{0,4}$/', $rule)) {
            // 不是正则表达式则两端补上/
            $rule = '/^' . $rule . '$/';
        }
        return 1 === preg_match($rule, (string) $value);
    }

    /**
     * 验证数字是否在此范围
     * @param $value
     * @param $min
     * @param $max
     * @return bool
     */
    protected function betweenValidate($value, $min, $max)
    {
        return $value >= $min && $value <= $max;
    }

    /**
     * 验证数字是否不在此范围
     * @param $value
     * @param $min
     * @param $max
     * @return bool
     */
    protected function notBetweenValidate($value, $min, $max)
    {
        return $value < $min || $value > $max;
    }

    /**
     * 字符长度验证
     * @param $value
     * @param $min
     * @param $max
     * @return bool
     */
    protected function lengthValidate($value, $min, $max)
    {
        return $min <= mb_strlen($value) && mb_strlen($value) <= $max;
    }

    /**
     * 验证是否在范围内
     * @return bool
     */
    protected function inValidate($value, ...$rule)
    {
        return in_array($value, $rule);
    }

    /**
     * 验证不在范围内
     * @param $value
     * @param array ...$rule
     * @return bool
     */
    protected function notInValidate($value, ...$rule)
    {
        return ! in_array($value, $rule);
    }

    /**
     * 验证数据最大长度
     * @param $value
     * @param $rule
     * @return bool
     */
    protected function maxValidate($value, $rule)
    {
        if (is_array($value)) {
            $length = count($value);
        }else {
            $length = mb_strlen((string) $value);
        }
        return $length <= $rule;
    }

    /**
     * 验证数据最小长度
     * @param $value
     * @param $rule
     * @return bool
     */
    protected function minValidate($value, $rule)
    {
        if (is_array($value)) {
            $length = count($value);
        }else {
            $length = mb_strlen((string) $value);
        }
        return $length >= $rule;
    }

    /**
     * 验证是否和某个字段的值一致
     * @param $value
     * @param $field
     * @return bool
     */
    protected function confirmValidate($value, $field)
    {
        return isset($this->data[$field]) && $this->data[$field] === $value;
    }

    /**
     * 验证是否和某个字段的值是否不同
     * @param $value
     * @param $diffValue
     * @return bool
     */
    protected function differentValidate($value, $diffValue)
    {
        return $diffValue != $value;
    }

    /**
     * 验证是否大于等于某个值
     * @param $value
     * @param $val
     * @return bool
     */
    protected function egtValidate($value, $val)
    {
        return $value >= $val;
    }

    /**
     * 验证是否大于某个值
     * @param $value
     * @param $val
     * @return bool
     */
    protected function gtValidate($value, $val)
    {
        return $value > $val;
    }

    /**
     * 验证是否小于等于某个值
     * @param $value
     * @param $val
     * @return bool
     */
    protected function eltValidate($value, $val)
    {
        return $value <= $val;
    }

    /**
     * 验证是否小于某个值
     * @param $value
     * @param $val
     * @return bool
     */
    protected function ltValidate($value, $val)
    {
        return $value < $val;
    }

    /**
     * 验证是否等于某个值
     * @param $value
     * @param $val
     * @return bool
     */
    protected function eqValidate($value, $val)
    {
        return $value == $val;
    }

    /**
     * 获取错误信息
     * @return array
     */
    public function getMessages()
    {
        return $this->errorMessages;
    }

    /**
     * 获取第一个错误信息
     * @return mixed
     */
    public function getFirstError()
    {
        if (empty($this->errorMessages)) {
            return '';
        }

        reset($this->errorMessages[0]);
        return current($this->errorMessages[0]);
    }
}
