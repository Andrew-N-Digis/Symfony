?php
declare(strict_types=1);

namespace App\Controller;

use App\Entity\Post;
use App\Entity\User;
use App\Repository\PostRepository;
use App\Repository\UnreadPostRepository;
use App\Service\User\UnreadPostService;
use Knp\Component\Pager\PaginatorInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @IsGranted("ROLE_USER")
 * @Route("/api/posts", name="post_")
 */
class PostController extends AbstractController
{
    /**
     * @Route("/", name="post-cabinet")
     */
    public function index(
        Request $request,
        PostRepository $postRepository,
        UnreadPostService $unreadPostService,
        PaginatorInterface $paginator
    ): Response
    {
        try {
            $user = $this->getUser();
            $query = $postRepository->findAllQueryByPublishedAt();

            $unreadPostService->markAsRead($user);

            $data = $paginator->paginate(
                $query,
                $request->query->getInt('page', 1),
                Post::POST_LIMIT
            );

            return $this->json([
                'data' => $this->getData($data),
                'unread_post' => true,
                'currentPage' => $data->getCurrentPageNumber(),
                'last_page' => ceil($data->getTotalItemCount() / Post::POST_LIMIT),
                'total_items' => $data->getTotalItemCount(),
                'limit' => Post::POST_LIMIT,
            ], 200);
        } catch (\Exception $exception) {
            return $this->json(['message' => $exception->getMessage()], 400);
        }
    }

    /**
     * @Route("/unread", name="post-unread")
     */
    public function getUnreadStatus(UnreadPostRepository $unreadPostRepository): Response
    {
        try {
            $user = $this->getUser();

            $unreadPost = $unreadPostRepository->findOneBy(['user' => $user]);

            return $this->json($unreadPost->getUnread() > 0);
        } catch (\Exception $exception) {
            return $this->json(['message' => $exception->getMessage()], 400);
        }
    }

    private function getData(PaginatorInterface $data, array $items = []): array
    {
        foreach ($data->getItems() as $item) {
            array_push($items, [
                'id' => $item->getId(),
                'published_at' => $item->getPublishedAt(),
                'lang' => $item->getLang(),
                'title' => $item->getTitle(),
                'body' => $item->getBody(),
                'tag' => $item->getTag(),
            ]);
        }

        return $items;
    }
}