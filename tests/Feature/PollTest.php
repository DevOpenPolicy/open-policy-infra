<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Poll;
use App\Models\PollOption;
use Illuminate\Support\Str;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use Laravel\Sanctum\Sanctum;

class PollTest extends TestCase
{
    // use RefreshDatabase; 



    public function test_user_can_create_poll()
    {
        $user = User::create([
            'first_name' => 'Test',
            'last_name' => 'User',
            'email' => 'test' . Str::random(10) . '@example.com',
            'password' => bcrypt('password'),
            'phone' => Str::random(10),
            'postal_code' => '12345',
            'gender' => 'Male',
            'age' => 25,
        ]);
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/app/v1/polls/create', [
            'title' => 'Test Poll',
            'options' => [
                ['option_text' => 'Option 1', 'color' => '#FF0000'],
                ['option_text' => 'Option 2', 'color' => '#00FF00'],
            ]
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure(['message', 'poll' => ['options']]);

        $this->assertDatabaseHas('polls', ['title' => 'Test Poll']);
        $this->assertDatabaseHas('poll_options', ['option_text' => 'Option 1']);
    }

    public function test_user_can_vote_once()
    {
        $user = User::create([
            'first_name' => 'Test',
            'last_name' => 'User',
            'email' => 'test' . Str::random(10) . '@example.com',
            'password' => bcrypt('password'),
            'phone' => Str::random(10),
            'postal_code' => '12345',
            'gender' => 'Male',
            'age' => 25,
        ]);
        Sanctum::actingAs($user);

        $poll = Poll::create(['user_id' => $user->id, 'title' => 'Voting Poll']);
        $option1 = PollOption::create(['poll_id' => $poll->id, 'option_text' => 'Opt 1']);
        $option2 = PollOption::create(['poll_id' => $poll->id, 'option_text' => 'Opt 2']);

        // Vote for option 1
        $response = $this->postJson('/api/app/v1/polls/vote', [
            'poll_id' => $poll->id,
            'poll_option_id' => $option1->id
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('poll_options', ['id' => $option1->id, 'vote_count' => 1]);

        // Try to vote again for option 2
        $response2 = $this->postJson('/api/app/v1/polls/vote', [
            'poll_id' => $poll->id,
            'poll_option_id' => $option2->id
        ]);

        $response2->assertStatus(403); // Or 400 depending on implementation
        $this->assertDatabaseHas('poll_options', ['id' => $option2->id, 'vote_count' => 0]);
    }

    public function test_can_list_polls()
    {
        $user = User::create([
            'first_name' => 'Test',
            'last_name' => 'User',
            'email' => 'test' . Str::random(10) . '@example.com',
            'password' => bcrypt('password'),
            'phone' => Str::random(10),
            'postal_code' => '12345',
            'gender' => 'Male',
            'age' => 25,
        ]);
        Sanctum::actingAs($user);

        Poll::create(['user_id' => $user->id, 'title' => 'List Poll']);

        $response = $this->getJson('/api/app/v1/polls');

        $response->assertStatus(200)
            ->assertJsonFragment(['title' => 'List Poll']);
    }
    public function test_user_can_edit_poll()
    {
        $user = User::create([
            'first_name' => 'Test',
            'last_name' => 'User',
            'email' => 'test' . Str::random(10) . '@example.com',
            'password' => bcrypt('password'),
            'phone' => Str::random(10),
            'postal_code' => '12345',
            'gender' => 'Male',
            'age' => 25,
        ]);
        Sanctum::actingAs($user);

        $poll = Poll::create(['user_id' => $user->id, 'title' => 'Original Title']);
        $option1 = PollOption::create(['poll_id' => $poll->id, 'option_text' => 'Opt 1', 'order' => 1]);
        $option2 = PollOption::create(['poll_id' => $poll->id, 'option_text' => 'Opt 2', 'order' => 2]);

        $response = $this->postJson('/api/app/v1/polls/update', [
            'poll_id' => $poll->id,
            'title' => 'Updated Title',
            'options' => [
                ['id' => $option1->id, 'option_text' => 'Updated Opt 1', 'order' => 1],
                // Option 2 removed (not present in list)
                ['option_text' => 'New Opt 3', 'order' => 3], // New option
            ]
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('polls', ['id' => $poll->id, 'title' => 'Updated Title']);
        $this->assertDatabaseHas('poll_options', ['id' => $option1->id, 'option_text' => 'Updated Opt 1']);
        $this->assertDatabaseHas('poll_options', ['option_text' => 'New Opt 3', 'poll_id' => $poll->id]);
        $this->assertDatabaseMissing('poll_options', ['id' => $option2->id]); // Should be deleted
    }

    public function test_user_can_delete_poll()
    {
        $user = User::create([
            'first_name' => 'Test',
            'last_name' => 'User',
            'email' => 'test' . Str::random(10) . '@example.com',
            'password' => bcrypt('password'),
            'phone' => Str::random(10),
            'postal_code' => '12345',
            'gender' => 'Male',
            'age' => 25,
        ]);
        Sanctum::actingAs($user);

        $poll = Poll::create(['user_id' => $user->id, 'title' => 'To Be Deleted']);
        $option1 = PollOption::create(['poll_id' => $poll->id, 'option_text' => 'Opt 1']);

        $response = $this->postJson('/api/app/v1/polls/delete', [
            'poll_id' => $poll->id,
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseMissing('polls', ['id' => $poll->id]);
        $this->assertDatabaseMissing('poll_options', ['id' => $option1->id]);
    }
}
