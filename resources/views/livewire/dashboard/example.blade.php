<?php

use Livewire\Volt\Component;

new class extends Component
{
    public $message = 'Hello from the example component!';
    
    public function mount()
    {
        // Component initialization logic here
    }
    
    public function updateMessage()
    {
        $this->message = 'Message updated at ' . now()->format('H:i:s');
    }
}; ?>

<div class="space-y-6">
    <!-- Example Component Content -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-medium text-gray-900">Example Component</h3>
            <button wire:click="updateMessage" 
                    class="bg-primary text-white px-4 py-2 rounded-md text-sm font-medium hover:bg-primary-dark transition-colors">
                Update Message
            </button>
        </div>
        
        <div class="space-y-4">
            <p class="text-gray-600">{{ $message }}</p>
            
            <div class="bg-gray-50 rounded-lg p-4">
                <h4 class="text-sm font-medium text-gray-900 mb-2">Features Available:</h4>
                <ul class="text-sm text-gray-600 space-y-1">
                    <li>• Authentication checking</li>
                    <li>• Business switching</li>
                    <li>• Responsive sidebar</li>
                    <li>• User menu and notifications</li>
                    <li>• Livewire integration</li>
                </ul>
            </div>
        </div>
    </div>
    
    <!-- Additional Example Content -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <h4 class="text-lg font-medium text-gray-900 mb-4">Card 1</h4>
            <p class="text-gray-600">This is an example card that demonstrates the layout structure.</p>
        </div>
        
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <h4 class="text-lg font-medium text-gray-900 mb-4">Card 2</h4>
            <p class="text-gray-600">Another example card showing the responsive grid layout.</p>
        </div>
    </div>
</div>
