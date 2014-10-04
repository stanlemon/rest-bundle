<?php
namespace Lemon\RestBundle\Event;

class RestEvents
{
    const PRE_SEARCH = "lemon_rest.event.pre_search";
    const POST_SEARCH = "lemon_rest.event.post_search";
    const PRE_CREATE = "lemon_rest.event.pre_create";
    const POST_CREATE = "lemon_rest.event.post_create";
    const PRE_RETRIEVE = "lemon_rest.event.pre_retrieve";
    const POST_RETRIEVE = "lemon_rest.event.post_retrieve";
    const PRE_UPDATE = "lemon_rest.event.pre_update";
    const POST_UPDATE = "lemon_rest.event.post_update";
    const PRE_DELETE = "lemon_rest.event.pre_delete";
    const POST_DELETE = "lemon_rest.event.post_delete";
}
