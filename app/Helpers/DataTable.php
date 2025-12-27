<?php

namespace App\Helpers;

use Illuminate\Support\Facades\DB;

class DataTable {

  protected static function countWithGroupAware($query){
    // Clone to avoid mutating original
    $clone = clone $query;

    // Check if the query has groupBy
    if (!empty($clone->getQuery()->groups)) {
      // Convert to subquery and count the rows from that
      $sub = DB::table(DB::raw("({$clone->toSql()}) as sub"))
        ->mergeBindings($clone->getQuery());

      return $sub->count();
    }

    // No groupBy? Normal count is fine
    return $clone->count();
  }

  public static function ajaxTable($model, $mappingFunction = 'dataTableColumns'){
    $offset = request('start');
    $take   = request('length');
    $search = request('search')['value'];

    $search = preg_replace("/[^a-zA-Z0-9\s.@]+/", '', $search);

    $recordsTotal = self::countWithGroupAware($model);
    if($search){
      $model = $model->search($search);
    }

    $filteredModel = clone $model;

    $model = $model->take($take)->offset($offset);

    $orderBy = null;
    $orderDir = null;

    $temp = clone $model;
    $tempData = $temp->first();
    if ($tempData) {
      $class = get_class($tempData);
      if (method_exists($class, $mappingFunction)) {
        $columns = $class::$mappingFunction();
        $orderBy = $columns[request('order')[0]['column']];
        $orderDir = request('order')[0]['dir'];
      }
    }

    if ($orderBy && $orderDir) {
      $model = $model->orderBy($orderBy, $orderDir);
    }

    $model = $model->get();

    return [
      'draw' => intval(request('draw')),
      'recordsTotal' => $recordsTotal,
      'recordsFiltered' => self::countWithGroupAware($filteredModel),
      'data' => $model
    ];
  }

}
