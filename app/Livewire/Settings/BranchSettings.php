<?php

namespace App\Livewire\Settings;

use App\Models\Branch;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Attributes\On;
use Livewire\Component;

class BranchSettings extends Component
{
    use LivewireAlert;

    // Form fields
    public $branchName;
    public $branchAddress;
    public $branchLat = '26.9125';
    public $branchLng = '75.7875';

    // States
    public $isEditing = false;
    public $confirmDeleteBranchModal = false;
    public $activeBranch = null;
    public $activeBranchId = null;
    public $formMode = 'add'; // 'add' or 'edit'

    public function mount()
    {
        $this->resetForm();
    }

    public function resetForm()
    {
        $this->branchName = '';
        $this->branchAddress = '';
        $this->branchLat = '26.9125';
        $this->branchLng = '75.7875';
        $this->activeBranchId = null;
        $this->formMode = 'add';
        $this->isEditing = false;
    }

    private function checkBranchLimit(): bool
    {
        if (!in_array('Change Branch', restaurant_modules(), true)) {
            abort(403, __('messages.invalidRequest'));
        }

        $restaurant = restaurant();
        $branchLimit = $restaurant->package->branch_limit;

        if (is_null($branchLimit) || $branchLimit === -1) {
            return true;
        }

        if ($branchLimit === 0 || $restaurant->branches()->count() >= $branchLimit) {
            $this->alert('error', __('messages.branchLimitReached'), [
                'toast' => true,
                'position' => 'top-end',
                'showCancelButton' => false,
                'cancelButtonText' => __('app.close')
            ]);
            return false;
        }

        return true;
    }

    public function createMode()
    {
        if (!$this->checkBranchLimit()) {
            return;
        }
        $this->dispatch('initAddressMap');

        $this->resetForm();
        $this->formMode = 'add';
        $this->isEditing = true;
    }

    public function editMode($id)
    {
        $branch = Branch::findOrFail($id);
        $this->activeBranchId = $branch->id;
        $this->activeBranch = $branch;
        $this->branchName = $branch->name;
        $this->branchAddress = $branch->address;
        $this->branchLat = $branch->lat ?? '26.9125';
        $this->branchLng = $branch->lng ?? '75.7875';
        $this->formMode = 'edit';
        $this->isEditing = true;
        $this->dispatch('initAddressMap');
    }

    public function cancelEdit()
    {
        $this->resetForm();
    }

    public function showDeleteBranch($id)
    {
        $this->activeBranch = Branch::findOrFail($id);
        $this->activeBranchId = $id;
        $this->confirmDeleteBranchModal = true;
    }

    public function deleteBranch()
    {
        Branch::destroy($this->activeBranchId);
        $this->activeBranch = null;
        $this->activeBranchId = null;
        $this->confirmDeleteBranchModal = false;

        session(['branches' => Branch::get()]);

        $this->alert('success', __('messages.branchDeleted'), [
            'toast' => true,
            'position' => 'top-end',
            'showCancelButton' => false,
            'cancelButtonText' => __('app.close')
        ]);
    }

    #[On('updateLivewireMapProperties')]
    public function updateLivewireMapProperties($lat, $lng)
    {
        $this->branchLat = $lat;
        $this->branchLng = $lng;
    }

    public function saveBranch()
    {
        if ($this->formMode == 'add' && !$this->checkBranchLimit()) {
            $this->alert('error', __('messages.invalidRequest'), [
                'toast' => true,
                'position' => 'top-end',
                'showCancelButton' => false,
                'cancelButtonText' => __('app.close')
            ]);
            return;
        }

        // Different validation rules based on mode
        if ($this->formMode == 'add') {
            $this->validate([
                'branchName' => 'required|unique:branches,name,null,id,restaurant_id,' . restaurant()->id,
                'branchAddress' => 'required',
                'branchLat' => 'required|numeric|between:-90,90',
                'branchLng' => 'required|numeric|between:-180,180',
            ]);

            Branch::create([
                'name' => $this->branchName,
                'restaurant_id' => restaurant()->id,
                'address' => $this->branchAddress,
                'lat' => $this->branchLat,
                'lng' => $this->branchLng,
            ]);

            $this->alert('success', __('messages.branchAdded'), [
                'toast' => true,
                'position' => 'top-end',
                'showCancelButton' => false,
                'cancelButtonText' => __('app.close')
            ]);
        } else {
            $this->validate([
                'branchName' => 'required|unique:branches,name,'.$this->activeBranchId.',id,restaurant_id,' . restaurant()->id,
                'branchAddress' => 'required',
                'branchLat' => 'required|numeric|between:-90,90',
                'branchLng' => 'required|numeric|between:-180,180'
            ]);

            Branch::where('id', $this->activeBranchId)->update([
                'name' => $this->branchName,
                'restaurant_id' => restaurant()->id,
                'address' => $this->branchAddress,
                'lat' => $this->branchLat,
                'lng' => $this->branchLng,
            ]);

            session()->forget(['restaurant', 'branch']);

            $this->alert('success', __('messages.branchUpdated'), [
                'toast' => true,
                'position' => 'top-end',
                'showCancelButton' => false,
                'cancelButtonText' => __('app.close')
            ]);
        }

        session(['branches' => Branch::get()]);
        $this->resetForm();
    }

    public function render()
    {
        $branches = Branch::where('restaurant_id', restaurant()->id)->get();
        $mapApiKey = restaurant()->map_api_key ?? null;

        return view('livewire.settings.branch-settings', [
            'branches' => $branches,
            'mapApiKey' => $mapApiKey
        ]);
    }
}
