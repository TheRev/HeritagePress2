<?php

/**
 * HeritagePress Date Utilities
 * (Work in progress - basic version for now)
 */
class HP_Date_Utils
{
  /**
   * Format a date string to Y-m-d or return the original if invalid.
   */
  public static function format_date($date)
  {
    if (empty($date)) {
      return '';
    }
    $timestamp = strtotime($date);
    if ($timestamp === false) {
      return esc_html($date); // fallback to raw if not parseable
    }
    return date('Y-m-d', $timestamp);
  }
}
