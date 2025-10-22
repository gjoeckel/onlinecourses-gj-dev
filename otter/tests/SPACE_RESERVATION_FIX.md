# Space Reservation Fix: Clear Button vs None Preset

## Issue Description

When the Clear button was clicked, the message was dismissed and exact space was reserved correctly.
When the "None" preset was selected, which should trigger the EXACT same function as Clear,
the reserved space height was slightly less than the actual message element.

## Root Cause Analysis

The issue was caused by **conflicting function calls** in the "None" preset handler:

### Clear Button (Working Correctly):
1. Calls `clearMessageDisplay()` 
2. Sets class to: `'date-range-status visually-hidden-but-space'`
3. Clears date fields
4. Updates button states

### "None" Preset (Problematic):
1. Calls `clearMessageDisplay()` ✅
2. Sets class to: `'date-range-status visually-hidden-but-space'` ✅
3. Clears date fields ✅
4. Updates button states ✅
5. **BUG**: Calls `updateActiveRangeMessage()` in setTimeout ❌

### The Problem:
The `updateActiveRangeMessage()` function was being called for the "None" preset, which:
- Checked if both dates were valid (they were now empty)
- Since dates were empty, it called the `else` block
- This `else` block **overwrote** the CSS classes that were just set by `clearMessageDisplay()`
- The overwrite caused inconsistent CSS class application and different space reservation

## Files Modified

### 1. reports/js/reports-messaging.js
**Lines 175-185**: Modified the preset radio change handler to skip `updateActiveRangeMessage()` for "none" preset:

```javascript
// Update button states based on current values (DRY approach)
updateButtonStates();
// For "none" preset, don't call updateActiveRangeMessage as it will override the cleared state
if (this.value !== 'none') {
  setTimeout(() => {
    if (typeof window.updateActiveRangeMessage === 'function') {
      window.updateActiveRangeMessage();
    }
    // Ensure button states are updated after DOM changes
    updateButtonStates();
  }, 0);
}
```

### 2. reports/js/date-range-picker.js
**Lines 144-156**: Modified the preset radio change handler to skip `updateActiveRangeMessage()` for "none" preset:

```javascript
// For "none" preset, don't call updateActiveRangeMessage as it will override the cleared state
if (this.value !== 'none') {
  setTimeout(() => {
    if (typeof window.updateActiveRangeMessage === 'function') {
      window.updateActiveRangeMessage();
    }
    // Ensure button states are updated after DOM changes
    updateApplyButtonEnabled();
  }, 0);
} else {
  // For "none" preset, just update button states
  updateApplyButtonEnabled();
}
```

**Lines 420-430**: Modified the Edit Date Range button logic to skip `updateActiveRangeMessage()` for "none" preset:

```javascript
// Check if a preset is selected and trigger Active Date Range display
const selectedPreset = document.querySelector('input[name="date-preset"]:checked');
if (selectedPreset && selectedPreset.value !== 'none') {
  // Use a timeout to avoid race conditions and ensure DOM is ready
  setTimeout(() => {
    if (typeof window.updateActiveRangeMessage === 'function') {
      window.updateActiveRangeMessage();
    }
  }, 1000); // 1 second timeout as requested
}
// For "none" preset or no preset, don't call updateActiveRangeMessage as it will override the cleared state
```

## CSS Classes Involved

### Correct Space Reservation:
```css
.date-range-status.visually-hidden-but-space {
  visibility: hidden;
  min-height: 3.25rem;
  margin: 0;
  padding: 0.75rem;
  background: transparent !important;
  border: none !important;
  box-sizing: border-box;
}
```

### Base Date Range Status:
```css
.date-range-status {
  padding: 0.75rem;
  margin: 0;
  border-radius: 4px;
  font-weight: bold;
  background-color: #f5f6fa;
  color: #333;
  border: 1px solid #dcdde1;
  text-align: center;
  min-height: 3.25rem;
  display: flex;
  align-items: center;
  justify-content: center;
  box-sizing: border-box;
}
```

## Testing

Created test file: `tests/clear_vs_none_space_test.html`

**Test Steps:**
1. Set some dates (e.g., "01-01-24" to "01-31-24")
2. Click "Clear" button - note the height
3. Set dates again  
4. Select "None" preset - note the height
5. Heights should now be identical

## Result

After the fix:
- ✅ Clear button and "None" preset now have identical space reservation
- ✅ Both use the same `clearMessageDisplay()` function
- ✅ No conflicting `updateActiveRangeMessage()` calls for "none" preset
- ✅ Consistent CSS class application
- ✅ Proper space reservation maintained

## Impact

- **UI Consistency**: Clear button and "None" preset now behave identically
- **No Layout Shift**: Consistent space reservation prevents layout jumping
- **Better UX**: Users get predictable behavior regardless of how they clear the date range
- **Code Maintainability**: Eliminated redundant and conflicting function calls 