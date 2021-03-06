<?php
namespace History\Entities\Models;

class Question extends AbstractModel
{
    /**
     * @var array
     */
    protected $fillable = [
        'name',
        'choices',
        'passed',
        'approval',
    ];

    //////////////////////////////////////////////////////////////////////
    //////////////////////////// RELATIONSHIPS ///////////////////////////
    //////////////////////////////////////////////////////////////////////

    /**
     * @codeCoverageIgnore
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function request()
    {
        return $this->belongsTo(Request::class);
    }

    /**
     * @codeCoverageIgnore
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function votes()
    {
        return $this->hasMany(Vote::class);
    }

    //////////////////////////////////////////////////////////////////////
    ///////////////////////////// ATTRIBUTES /////////////////////////////
    //////////////////////////////////////////////////////////////////////

    /**
     * Get which choice was picked by the majority.
     *
     * @return int
     */
    public function getMajorityChoiceAttribute()
    {
        $majority = $this->votes->groupByCounts('choice')->sort();
        $majority = $majority->keys()->last();

        return $majority;
    }
}
