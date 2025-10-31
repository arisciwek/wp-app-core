# TODO-1189: Add GROUP BY Support to DataTableQueryBuilder

**Date**: 2025-10-31
**Status**: ✅ COMPLETED
**Context**: Foundation for Perfex CRM style centralized datatable system

## Problem

DataTableQueryBuilder tidak support GROUP BY clause, diperlukan untuk data aggregation (seperti GROUP_CONCAT untuk jurisdictions di division datatable).

## Solution

Add GROUP BY support ke DataTableQueryBuilder dengan:

1. **Property baru**: `private $group_by = '';`
2. **Setter method**: `set_group_by($group_by)`
3. **Builder method**: `build_group_by()`
4. **Update build_query()**: Include GROUP BY antara WHERE dan ORDER BY
5. **Update count methods**: Use `COUNT(DISTINCT ...)` when GROUP BY is set

## Files Modified

**wp-app-core/src/Models/DataTable/DataTableQueryBuilder.php**
- Line 74-79: Added `$group_by` property
- Line 206-220: Added `set_group_by()` setter
- Line 327-339: Added `build_group_by()` builder
- Line 392-410: Updated `build_query()`
- Line 442-471: Updated `count_total()`
- Line 479-499: Updated `count_filtered()`

## Query Order

```sql
SELECT ... FROM ... WHERE ... GROUP BY ... ORDER BY ... LIMIT ...
```

## Usage (via Filter Hook)

```php
add_filter($this->get_filter_hook('query_builder'),
           [$this, 'set_query_builder_group_by'], 10, 3);

public function set_query_builder_group_by($query_builder, $request_data, $model) {
    $query_builder->set_group_by('d.id');
    return $query_builder;
}
```

## Testing

✅ Basic query with GROUP BY
✅ COUNT(DISTINCT) for totals
✅ GROUP_CONCAT aggregation
✅ Search with GROUP BY
✅ Pagination correct
✅ Backward compatible

## Benefits

- Centralized pattern maintained
- Reusable for all DataTable models
- Perfex CRM style
- Backward compatible

## Related

- wp-agency TODO-3092 (Division datatable - first use case)
