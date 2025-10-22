// date-utils.js
// Centralized date formatting utilities for reports pages

/**
 * Get today's date in MM-DD-YY format
 * @returns {string} Today's date in MM-DD-YY format
 */
export function getTodayMMDDYY() {
  const today = new Date();
  const mm = String(today.getMonth() + 1).padStart(2, '0');
  const dd = String(today.getDate()).padStart(2, '0');
  const yy = String(today.getFullYear()).slice(-2);
  return `${mm}-${dd}-${yy}`;
}

/**
 * Get previous month date range in MM-DD-YY format
 * @returns {object} Object with start and end dates in MM-DD-YY format
 */
export function getPrevMonthRangeMMDDYY() {
  const now = new Date();
  const prevMonth = now.getMonth() === 0 ? 11 : now.getMonth() - 1;
  const year = now.getMonth() === 0 ? now.getFullYear() - 1 : now.getFullYear();
  const firstDay = new Date(year, prevMonth, 1);
  const lastDay = new Date(year, prevMonth + 1, 0);
  
  function toMMDDYY(date) {
    const mm = String(date.getMonth() + 1).padStart(2, '0');
    const dd = String(date.getDate()).padStart(2, '0');
    const yy = String(date.getFullYear()).slice(-2);
    return `${mm}-${dd}-${yy}`;
  }
  
  return {
    start: toMMDDYY(firstDay),
    end: toMMDDYY(lastDay)
  };
}

/**
 * Validate MM-DD-YY format
 * @param {string} val - Date string to validate
 * @returns {boolean} True if valid MM-DD-YY format
 */
export function isValidMMDDYYFormat(val) {
  return /^\d{2}-\d{2}-\d{2}$/.test(val);
}

/**
 * Check if MM-DD-YY string is a real calendar date
 * @param {string} val - Date string to validate
 * @returns {boolean} True if valid calendar date
 */
export function isValidCalendarDateMMDDYY(val) {
  if (!/^\d{2}-\d{2}-\d{2}$/.test(val)) return false;
  const [mm, dd, yy] = val.split('-').map(Number);
  if (mm < 1 || mm > 12) return false;
  const yyyy = yy < 50 ? 2000 + yy : 1900 + yy;
  const daysInMonth = new Date(yyyy, mm, 0).getDate();
  if (dd < 1 || dd > daysInMonth) return false;
  return true;
}

/**
 * Get the most recent closed quarter's start and end dates in MM-DD-YY format
 * @param {string} q - Quarter (q1, q2, q3, q4)
 * @returns {object} { start, end } in MM-DD-YY
 */
export function getMostRecentClosedQuarterMMDDYY(q) {
  const today = new Date();
  const year = today.getFullYear();
  const month = today.getMonth();
  let fyStartYear = month >= 6 ? year : year - 1;
  let prevFY = fyStartYear - 1;
  let currFY = fyStartYear;
  let currentQuarter;
  if (month >= 6 && month <= 8) currentQuarter = 'q1';
  else if (month >= 9 && month <= 11) currentQuarter = 'q2';
  else if (month >= 0 && month <= 2) currentQuarter = 'q3';
  else if (month >= 3 && month <= 5) currentQuarter = 'q4';
  let start, end;
  if (q === 'q1') {
    if (currentQuarter === 'q1') {
      start = new Date(prevFY, 6, 1); end = new Date(prevFY, 8, 30);
    } else {
      start = new Date(currFY, 6, 1); end = new Date(currFY, 8, 30);
    }
  } else if (q === 'q2') {
    if (currentQuarter === 'q1' || currentQuarter === 'q2') {
      start = new Date(prevFY, 9, 1); end = new Date(prevFY, 11, 31);
    } else {
      start = new Date(currFY, 9, 1); end = new Date(currFY, 11, 31);
    }
  } else if (q === 'q3') {
    if (currentQuarter === 'q1' || currentQuarter === 'q2' || currentQuarter === 'q3') {
      start = new Date(prevFY + 1, 0, 1); end = new Date(prevFY + 1, 2, 31);
    } else {
      start = new Date(currFY + 1, 0, 1); end = new Date(currFY + 1, 2, 31);
    }
  } else if (q === 'q4') {
    if (currentQuarter !== 'q4') {
      start = new Date(prevFY + 1, 3, 1); end = new Date(prevFY + 1, 5, 30);
    } else {
      start = new Date(prevFY + 1, 3, 1); end = new Date(prevFY + 1, 5, 30);
    }
  }
  function toMMDDYY(date) {
    const mm = String(date.getMonth() + 1).padStart(2, '0');
    const dd = String(date.getDate()).padStart(2, '0');
    const yy = String(date.getFullYear()).slice(-2);
    return `${mm}-${dd}-${yy}`;
  }
  return { start: toMMDDYY(start), end: toMMDDYY(end) };
} 