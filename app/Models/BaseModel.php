<?php

namespace App\Models;

use App\Models\Concerns\FormatsDates;
use Illuminate\Database\Eloquent\Model;

class BaseModel extends Model
{
    use FormatsDates;
}
