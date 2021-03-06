<?php

namespace Modules\Ichat\Transformers;

use Illuminate\Http\Resources\Json\Resource;
use Modules\Iprofile\Transformers\UserTransformer;
use Illuminate\Support\Facades\Auth;

class ConversationUserTransformer extends Resource
{
  public function toArray($request)
  {
    $data = [
      'id' => $this->id,
      'conversationId' => $this->when( $this->conversation_id, $this->conversation_id ),
      'userId' => $this->when( $this->user_id, $this->user_id ),
      'lastMessageReaded' => $this->last_message_readed,
      'user' => new UserTransformer ( $this->user ),
      'conversation' => new ConversationTransformer ( $this->whenLoaded('conversation') ),

    ];
    return $data;
  }
}
