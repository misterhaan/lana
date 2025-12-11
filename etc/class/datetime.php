<?php

class FormatDate {
	public const Long = 'g:i A \o\n l F jS Y';

	public static function HowLongAgo(int $timestamp): string {
		return self::TimeSpan(abs(time() - $timestamp));
	}

	public static function TimeSpan(int $seconds): string {
		if ($seconds < 120)  // 2 minutes
			return $seconds . ' seconds';
		if ($seconds < 7200)  // 2 hours
			return round($seconds / 60, 0) . ' minutes';
		if ($seconds < 172800)  // 2 days
			return round($seconds / 3600, 0) . ' hours';
		if ($seconds < 1209600)  // 2 weeks
			return round($seconds / 86400, 0) . ' days';
		if ($seconds < 5259488)  // 2 months
			return round($seconds / 604800, 0) . ' weeks';
		if ($seconds < 63113818)  // 2 years
			return round($seconds / 2629739.52) . ' months';
		if ($seconds < 631138176)  // 20 years
			return round($seconds / 31556908.8) . ' years';
		return round($seconds / 315569088) . ' decades';
	}
}

class TimeTagData {
	public string $datetime = '';
	public string $display = '';
	public string $tooltip = '';

	public function __construct(string $format, string $timestamp, string $tooltipFormat = '') {
		$this->datetime = gmdate('c', $timestamp);
		if ($format == 'ago' || $format == 'since')
			$this->display = FormatDate::HowLongAgo($timestamp);
		//elseif ($format == 'smart')
		//	$this->display = FormatDate::SmartDate($user, $timestamp);
		else
			//$this->display = FormatDate::Local($format, $timestamp, $user);
			$this->display = date($format, $timestamp);
		if ($tooltipFormat)
			//$this->tooltip = FormatDate::Local($tooltipFormat, $timestamp, $user);
			$this->tooltip = date($tooltipFormat, $timestamp);
	}
}
