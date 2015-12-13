<?php

return array(

	/*
	|--------------------------------------------------------------------------
	| Validation Language Lines
	|--------------------------------------------------------------------------
	|
	| The following language lines contain the default error messages used by
	| the validator class. Some of these rules have multiple versions such
	| as the size rules. Feel free to tweak each of these messages here.
	|
	*/

	"accepted"             => "属性 :attribute 必须被同意.",
	"active_url"           => "属性 :attribute 不是有效的 URL.",
	"after"                => "属性 :attribute 必须是大于 :date 的日期.",
	"alpha"                => "属性 :attribute 只能包涵字母.",
	"alpha_dash"           => "属性 :attribute 只能包涵字母, 数组, 下划线.",
	"alpha_num"            => "属性 :attribute 只能包涵字母与数字.",
	"array"                => "属性 :attribute 必须是数组.",
	"before"               => "属性 :attribute 必须是小于 :date 的日期.",
	"between"              => array(
		"numeric" => "数值 :attribute 必须在 :min 和 :max 之间.",
		"file"    => "文件 :attribute 的大小必须在 :min 和 :max kb 之间.",
		"string"  => "字符 :attribute 的长度必须在 :min 和 :max 个字符之间.",
		"array"   => "数组 :attribute 的大小必须在 :min 和 :max 个元素之间.",
	),
	"boolean"              => "属性 :attribute 的值只能是 true 或者 false.",
	"confirmed"            => "属性 :attribute 的确认信息比匹配.",
	"date"                 => "属性 :attribute 不是有效的日期.",
	"date_format"          => "属性 :attribute 不能匹配到 :format.",
	"different"            => "属性 :attribute 和 :other 必须不同.",
	"digits"               => "属性 :attribute 必须是 :digits 个数字.",
	"digits_between"       => "属性 :attribute 必须是 :min 到 :max 个数字之间.",
	"email"                => "属性 :attribute 必须是有效的 email 地址.",
	"exists"               => "所选属性 :attribute 无效.",
	"image"                => "属性 :attribute 必须是图片.",
	"in"                   => "所选属性 :attribute 无效.",
	"integer"              => "属性 :attribute 必须是整数.",
	"ip"                   => "属性 :attribute 必须是有效的 IP 地址.",
	"max"                  => array(
		"numeric" => "数值 :attribute 不可以大于 :max.",
		"file"    => "文件大小 :attribute 不可以大于 :max kb.",
		"string"  => "字符长度 :attribute 不可超过 :max 个字符.",
		"array"   => "数组大小 :attribute 不能超过 :max 个元素.",
	),
	"mimes"                => "属性 :attribute 只能是类型: :values.",
	"min"                  => array(
		"numeric" => "数值 :attribute 不能小于 :min.",
		"file"    => "文件大小 :attribute 不能小于 :min kb.",
		"string"  => "字符长度 :attribute 不能少于 :min 个字符.",
		"array"   => "数组大小 :attribute 不能小于 :min 个元素.",
	),
	"not_in"               => "所选属性 :attribute 无效.",
	"numeric"              => "属性 :attribute 必须是数字.",
	"regex"                => "属性 :attribute 格式不对.",
	"required"             => "属性 :attribute 必填.",
	"required_if"          => "属性 :attribute 在 :other 是 :value 时必填.",
	"required_with"        => "属性 :attribute 在 :values 出现时必填.",
	"required_with_all"    => "属性 :attribute 在 :values 出现时必填.",
	"required_without"     => "属性 :attribute 在 :values 不出现时必填.",
	"required_without_all" => "属性 :attribute 在 :values 不出现时必填.",
	"same"                 => "属性 :attribute 与 :other 必须一样.",
	"size"                 => array(
		"numeric" => "数值 :attribute 必须等于 :size.",
		"file"    => "文件大小 :attribute 必须等于 :size kb.",
		"string"  => "字符长度 :attribute 必须等于 :size 个字符.",
		"array"   => "数组大小 :attribute 必须包含 :size 个元素.",
	),
	"unique"               => "属性 :attribute 不唯一.",
	"url"                  => "属性 :attribute 格式不正确.",
	"timezone"             => "属性 :attribute 必须是一个有效的时区.",

	/*
	|--------------------------------------------------------------------------
	| Custom Validation Language Lines
	|--------------------------------------------------------------------------
	|
	| Here you may specify custom validation messages for attributes using the
	| convention "attribute.rule" to name the lines. This makes it quick to
	| specify a specific custom language line for a given attribute rule.
	|
	*/

	'custom' => array(
		'attribute-name' => array(
			'rule-name' => 'custom-message',
		),
	),

	/*
	|--------------------------------------------------------------------------
	| Custom Validation Attributes
	|--------------------------------------------------------------------------
	|
	| The following language lines are used to swap attribute place-holders
	| with something more reader friendly such as E-Mail Address instead
	| of "email". This simply helps us make messages a little cleaner.
	|
	*/

	'attributes' => array(),

);
