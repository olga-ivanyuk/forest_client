<?php

namespace App\Console\Commands;

use App\HttpClients\PostHttpClient;
use App\Models\Post;
use Illuminate\Console\Command;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

class GoCommand extends Command
{
    protected $signature = 'go';
    protected $description = 'Command description';

    /**
     * @throws ConnectionException
     */
    public function handle(): void
    {
        $client = PostHttpClient::make()->login();

        //write all titles of posts to table
        foreach ($client->index() as $res) {
            Post::query()->firstOrCreate([
                'title' => $res ['title'],
            ]);
        }
        // store post
        $newPost = $client->store([
            'title' => 'New Post',
            'profile_id' => 1,
            'category_id' => 1,
        ]);
        $this->info('Post created: ' . json_encode($newPost));

        // update post
        $updatedPost = $client->update($newPost['id'],[
            'title' => 'Update Post',
            'profile_id' => 1,
            'category_id' => 1,
        ]);
        $this->info('Post updated: ' . json_encode($updatedPost));

        // delete post
        $deletePost = $client->delete($updatedPost['id']);
        $this->info('Post deleted: ' . ($deletePost ? 'success' : 'error'));
    }
}
