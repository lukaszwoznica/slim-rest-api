<?php

use App\Database;
use App\Middleware\BookExistentMiddleware;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;


$app->get('/', function (Request $request, Response $response) {
    $response->getBody()->write(file_get_contents('../App/Views/index.html'));
    return $response;
});

$app->get('/api', function (Request $request, Response $response){
    $host = $request->getUri()->getHost();
    $endpoints['books'] = "http://$host/api/v1/books{/id}";
    $endpoints['docs'] = "http://$host";
    $body['message'] = 'Slim REST API Help';
    $body['code'] = 200;
    $body['status'] = 'Ok';
    $body['endpoints'] = $endpoints;
    $response->getBody()->write(json_encode($body));

    return $response->withHeader('Content-Type', 'application/json');
});

$app->get('/api/v1/books', function (Request $request, Response $response) {
    $db = Database::getConnection();
    $sql = 'SELECT * FROM books';
    $stmt = $db->query($sql);
    $books = $stmt->fetchAll(PDO::FETCH_OBJ);
    $result = [
        'message' => 'Books successfully selected',
        'code' => 200,
        'status' => 'Ok',
        'books' => $books];
    $response->getBody()->write(json_encode($result));

    return $response->withHeader('Content-Type', 'application/json');
});

$app->get('/api/v1/books/{id:[0-9]+}', function (Request $request, Response $response, array $args) {
    $id = $args['id'];
    $db = Database::getConnection();
    $sql = 'SELECT * FROM books WHERE id = :id';

    $stmt = $db->prepare($sql);
    $stmt->bindValue(':id', $id, PDO::PARAM_INT);
    $stmt->execute();
    $book = $stmt->fetch(PDO::FETCH_OBJ);
    $result = [
        'message' => 'Book successfully selected',
        'code' => 200,
        'status' => 'Ok',
        'book' => $book];

    $response->getBody()->write(json_encode($result));

    return $response->withHeader('Content-Type', 'application/json');
})->add(new BookExistentMiddleware());

$app->post('/api/v1/books', function (Request $request, Response $response, array $args) {
    $inserted_id = '';
    $result['status'] = 'Error';

    $parsed_body = $request->getParsedBody();
    if ($parsed_body) {
        $title = $parsed_body['title'] ?? null;
        $author = $parsed_body['author'] ?? null;
        $number = $parsed_body['number'] ?? null;

        if (!$title || !$author || !$number) {
            $result['message'] = 'You must pass 3 parameters (title, author, number) to create a book';
            $code = 400;
        } else {
            $db = Database::getConnection();
            $sql = 'INSERT INTO books (title, author, number) VALUES (:title, :author, :number)';

            $stmt = $db->prepare($sql);
            $stmt->bindValue(':title', $title, PDO::PARAM_STR);
            $stmt->bindValue(':author', $author, PDO::PARAM_STR);
            $stmt->bindValue(':number', $number, PDO::PARAM_STR);

            try {
                $stmt->execute();
                $result['message'] = 'Book has been successfully created';
                $result['status'] = 'Ok';
                $code = 201;
                $inserted_id = (string) $db->lastInsertId();
            } catch (PDOException $e) {
                $code = 200;
                $result['message'] = 'Book with given number already exists';
            }
        }
    } else {
        $result['message'] = 'Request body is empty';
        $code = 400;
    }

    $result = array_merge($result, ['code' => $code]);
    $response->getBody()->write(json_encode($result));

    if ($code == 201) {
        $host = $request->getUri()->getHost();
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withHeader('Location', "http://$host/api/v1/books/$inserted_id")
            ->withStatus($code);
    }

    return $response->withHeader('Content-Type', 'application/json')->withStatus($code);
});

$app->put('/api/v1/books/{id:[0-9]+}', function (Request $request, Response $response, array $args) {
    $id = $args['id'];
    $code = 200;
    $result['status'] = 'Error';

    $parsed_body = $request->getParsedBody();
    if ($parsed_body) {
        $title = $parsed_body['title'] ?? null;
        $author = $parsed_body['author'] ?? null;
        $number = $parsed_body['number'] ?? null;

        if (!$title || !$author || !$number) {
            $result['message'] = 'You must pass 3 parameters (title, author, number) to update a book';
            $code = 400;
        } else {
            $db = Database::getConnection();
            $sql = 'UPDATE books SET title = :title, author = :author, number = :number WHERE id = :id';

            $stmt = $db->prepare($sql);
            $stmt->bindValue(':title', $title, PDO::PARAM_STR);
            $stmt->bindValue(':author', $author, PDO::PARAM_STR);
            $stmt->bindValue(':number', $number, PDO::PARAM_STR);
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            $stmt->execute();

            if ($stmt->rowCount() == 1) {
                $result['message'] = 'Book has been successfully updated';
                $result['status'] = 'Ok';
            } else {
                $result['message'] = 'Nothing has been updated';
            }
        }
    } else {
        $result['message'] = 'Request body is empty';
        $code = 400;
    }

    $result = array_merge($result, ['code' => $code, 'passed-book-details' => $parsed_body]);
    $response->getBody()->write(json_encode($result));

    return $response->withHeader('Content-Type', 'application/json')->withStatus($code);
})->add(new BookExistentMiddleware());

$app->delete('/api/v1/books/{id:[0-9]+}', function (Request $request, Response $response, array $args) {
    $id = $args['id'];
    $db = Database::getConnection();
    $sql = 'DELETE FROM books WHERE id = :id';

    $stmt = $db->prepare($sql);
    $stmt->bindValue(':id', $id, PDO::PARAM_INT);
    $stmt->execute();

    if ($stmt->rowCount() == 1) {
        $result['message'] = 'Book has been successfully deleted';
        $result['status'] = 'Ok';
    } else {
        $result['message'] = 'Book with given id does not exist';
        $result['status'] = 'Error';
    }

    $result['code'] = 200;
    $response->getBody()->write(json_encode($result));

    return $response->withHeader('Content-Type', 'application/json');
});