<?php

namespace App\Services\Parametrage;

use App\Models\Parametrage\Syndicat;

class SyndicatService
{
    public function __construct(private Syndicat $syndicat)
    {
    }

    public function getAllSyndicats()
    {
        return $this->syndicat->all();
    }

    public function getSyndicatById($id)
    {
        return $this->syndicat->findOrFail($id);
    }

    public function createSyndicat(array $data)
    {
        return $this->syndicat->create($data);
    }

    public function updateSyndicat($id, array $data)
    {
        $syndicat = $this->getSyndicatById($id);
        $syndicat->update($data);
        return $syndicat;
    }

    public function deleteSyndicat($id)
    {
        $syndicat = $this->getSyndicatById($id);
        $syndicat->delete();
    }
    
}
