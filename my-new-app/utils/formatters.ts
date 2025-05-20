/**
 * Formatter utilities for consistent data display
 */

/**
 * Format price values to have 2 decimal places and proper thousand separators
 * @param price The price to format
 * @returns Formatted price string
 */
export function formatPrice(price: number): string {
  return price.toLocaleString("en-US", {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
  });
}

/**
 * Format date to local string format
 * @param dateString Date string to format
 * @returns Formatted date string
 */
export function formatDate(dateString: string): string {
  if (!dateString) return "";

  const date = new Date(dateString);
  return date.toLocaleDateString("en-US", {
    year: "numeric",
    month: "short",
    day: "numeric",
  });
}

/**
 * Format a quantity with its unit
 * @param quantity The quantity value
 * @param unit The unit of measurement
 * @returns Formatted quantity with unit
 */
export function formatQuantity(quantity: number, unit: string = "pcs"): string {
  return `${quantity} ${unit}`;
}

/**
 * Format rating to show one decimal place
 * @param rating The rating value
 * @returns Formatted rating string
 */
export function formatRating(rating: number): string {
  return rating.toFixed(1);
}
