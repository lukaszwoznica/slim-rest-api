<?php

namespace App\Middleware;


use App\Database;
use PDO;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Slim\Psr7\Response;
use Slim\Routing\RouteContext;

class BookExistentMiddleware
{
    public function __invoke(Request $request, RequestHandler $handler): ResponseInterface
    {
        $id = RouteContext::fromRequest($request)->getRoute()->getArgument('id');
        $db = Database::getConnection();
        $sql = 'SELECT * FROM books WHERE id = :id';

        $stmt = $db->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        $book = $stmt->fetch(PDO::FETCH_OBJ);

        if (empty($book)) {
            $response = new Response();
            $error = [
                'message' => "Book with id: $id not found",
                'code' => 404,
                'status' => 'Error'
            ];
            $response->getBody()->write(json_encode($error));
            return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
        }

        $response = $handler->handle($request);

        return $response;
    }
}