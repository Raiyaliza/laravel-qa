<?php

namespace App;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'email', 'password',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    public function questions(){
      return $this->hasMany(Question::class);
    }

    // accessor
    public function getUrlAttribute()
    {
      return '#';
    }

    public function getAvatarAttribute()
    {
      // Pasting from gravatar.com
      $email = "someone@somewhere.com";
      $size = 32;
      return "https://www.gravatar.com/avatar/" . md5( strtolower( trim( $email ) ) ) . "?s=" . $size;
    }

    // relationship between user and answers
    public function answers()
    {
      return $this->hasMany(Answer::class);
    }

    // relationship between user and favorites
    public function favorites()
    {
      return $this->belongsToMany(Question::class, 'favorites')->withTimeStamps();
      // timestamps() ensures timestamps are added also, duh
    }

    // relationship between user and votable questions
    public function voteQuestions()
    {
      return $this->morphedByMany(Question::class, 'votable');
      // user is morphed by question & answer model
    }

    // relationship between user and votable answers
    public function voteAnswers()
    {
      return $this->morphedByMany(Answer::class, 'votable');
      // user is morphed by question & answer model
    }

    public function voteQuestion(Question $question, $vote)
    {
      // check if user has already voted; if not, we will add a row to table
      // if so, and the vote is opposite, we will update the table row
      $voteQuestions = $this->voteQuestions();

      $this->_vote($voteQuestions, $question, $vote);
    }

    public function voteAnswer(Answer $answer, $vote)
    {
      $voteAnswers = $this->voteAnswers();

      $this->_vote($voteAnswers, $answer, $vote);
    }

    private function _vote($relationship, $model, $vote)
    {
      $vote_exists = $relationship->where('votable_id', $model->id)->exists();
      if ($vote_exists)
      {
              $relationship->updateExistingPivot($model, ['vote' => $vote]);
      }
      else
      {
          $relationship->attach($model, ['vote' => $vote]);
      }

      $model->load('votes');
      $downVotes = (int) $model->downVotes()->sum('vote');
      $upVotes = (int) $model->upVotes()->sum('vote');

      $model->votes_count = $upVotes + $downVotes;
      $model->save();
    }

}
