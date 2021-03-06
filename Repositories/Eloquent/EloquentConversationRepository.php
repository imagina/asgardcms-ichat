<?php

namespace Modules\Ichat\Repositories\Eloquent;

use Modules\Ichat\Repositories\ConversationRepository;
use Modules\Core\Repositories\Eloquent\EloquentBaseRepository;
use Illuminate\Support\Facades\Auth;

class EloquentConversationRepository extends EloquentBaseRepository implements ConversationRepository
{
  public function getItemsBy($params)
  {
    // INITIALIZE QUERY
    $query = $this->model->query();
    /*== RELATIONSHIPS ==*/
    if (in_array('*', $params->include)) {//If Request all relationships
      $query->with([]);
    } else {//Especific relationships
      $includeDefault = ['users'];//Default relationships
      if (isset($params->include))//merge relations with default relationships
        $includeDefault = array_merge($includeDefault, $params->include);
      $query->with($includeDefault);//Add Relationships to query
    }
    // FILTERS
    if ($params->filter) {
      $filter = $params->filter;

      //Filter by date
      if (isset($filter->date)) {
        $date = $filter->date;//Short filter date
        $date->field = $date->field ?? 'created_at';
        if (isset($date->from))//From a date
          $query->whereDate($date->field, '>=', $date->from);
        if (isset($date->to))//to a date
          $query->whereDate($date->field, '<=', $date->to);
      }
      //Order by
      if (isset($filter->order)) {
        $orderByField = $filter->order->field ?? 'created_at';//Default field
        $orderWay = $filter->order->way ?? 'desc';//Default way
        $query->orderBy($orderByField, $orderWay);//Add order to query
      }

      //Filter by senderId
      if (isset($filter->senderId)) {
        $query->where("sender_id", $filter->senderId);
      }

      //Filter by receiverId
      if (isset($filter->receiverId)) {
        $query->where("receiver_id", $filter->receiverId);
      }

      // Filter by Status
      if (isset($filter->status)) {
        $query->where('status', $filter->status);
      }

      // Filter between any users
      if (isset($filter->between)) {
        foreach ($filter->between as $user){
          $query->whereHas('users', function ($query) use ($filter, $user){
            $query->where('user_id', $user);
          });
        }
      }

      // Filter by user
      if (isset($filter->myconversations)) {
        $query->wherehas('users', function ($query){
          $query->where('user_id', Auth::id());
        });
      }


    }
    /*== FIELDS ==*/
    if (isset($params->fields) && count($params->fields))
      $query->select($params->fields);
    /*== REQUEST ==*/
    if (isset($params->page) && $params->page) {
      return $query->paginate($params->take);
    } else {
      $params->take ? $query->take($params->take) : false;//Take
      return $query->get();
    }
  }
  public function getItem($criteria, $params = false)
  {
    //Initialize query
    $query = $this->model->query();
    /*== RELATIONSHIPS ==*/
    if (in_array('*', $params->include)) {//If Request all relationships
      $query->with([]);
    } else {//Especific relationships
      $includeDefault = [];//Default relationships
      if (isset($params->include))//merge relations with default relationships
        $includeDefault = array_merge($includeDefault, $params->include);
      $query->with($includeDefault);//Add Relationships to query
    }
    /*== FILTER ==*/
    if (isset($params->filter)) {
      $filter = $params->filter;
      // find translatable attributes
      $translatedAttributes = $this->model->translatedAttributes;
      if(isset($filter->field))
        $field = $filter->field;
      // filter by translatable attributes
      if (isset($field) && in_array($field, $translatedAttributes))//Filter by slug
        $query->whereHas('translations', function ($query) use ($criteria, $filter, $field) {
          $query->where('locale', $filter->locale)
            ->where($field, $criteria);
        });
      else
        // find by specific attribute or by id
        $query->where($field ?? 'id', $criteria);
    }
    /*== REQUEST ==*/
    return $query->first();
  }
  public function create($data)
  {
    //$data['sender_id'] = Auth::user()->id;
    $conversation = $this->model->create($data);
    if ($conversation) {
      $conversation->users()->sync(array_get($data, 'users', []));
    }
    return $conversation;
  }
  public function updateBy($criteria, $data, $params = false)
  {
    /*== initialize query ==*/
    $query = $this->model->query();
    /*== FILTER ==*/
    if (isset($params->filter)) {
      $filter = $params->filter;
      //Update by field
      if (isset($filter->field))
        $field = $filter->field;
    }
    /*== REQUEST ==*/
    $model = $query->where($field ?? 'id', $criteria)->first();
    return $model ? $model->update((array)$data) : false;
  }
  public function deleteBy($criteria, $params = false)
  {
    /*== initialize query ==*/
    $query = $this->model->query();
    /*== FILTER ==*/
    if (isset($params->filter)) {
      $filter = $params->filter;
      if (isset($filter->field))//Where field
        $field = $filter->field;
    }
    /*== REQUEST ==*/
    $model = $query->where($field ?? 'id', $criteria)->first();
    $model ? $model->delete() : false;
  }

}
