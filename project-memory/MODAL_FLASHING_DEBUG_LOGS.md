# Modal Flashing Debug Logs Addition

## Purpose

Added console logs to trace button clicks, modal initialization, show events, and global clicks to diagnose why the modal flashes on arbitrary clicks but not on button click.

## Files Modified

-   resources/views/components/forms/class-selector.blade.php: Added logs in JS script.

## Next Steps

-   User to refresh page, try clicking button and elsewhere, then report console output.

## Potential Insights

-   If button log doesn't appear: Listener not attaching.
-   If global clicks log but trigger modal: Misplaced handler.
-   If show called but closes: Closing event interfering.
