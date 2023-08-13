<?php
class QueryArticle extends connect{
  private $article;

  public function __construct(){
    parent::__construct();
  }

  public function setArticle(Article $article){
    $this->article = $article;
  }

  public function save(){
    if ($this->article->getId()){
      // IDがあるときは上書き
      $id = $this->article->getId();
      $title = $this->article->getTitle();
      $body = $this->article->getBody();
      $stmt = $this->dbh->prepare("UPDATE articles
                SET title=:title, body=:body, updated_at=NOW() WHERE id=:id");
      $stmt->bindParam(':title', $title, PDO::PARAM_STR);
      $stmt->bindParam(':body', $body, PDO::PARAM_STR);
      $stmt->bindParam(':id', $id, PDO::PARAM_INT);
      $stmt->execute();
    } else {
      // IDがなければ新規作成（新規作成時はtitleとbodyだけセットしている）
      // insertするタイミングで初めてidが割り振られるから
      $title = $this->article->getTitle();
      $body = $this->article->getBody();
      $stmt = $this->dbh->prepare("INSERT INTO articles (title, body, created_at, updated_at)
                VALUES (:title, :body, NOW(), NOW())");
      $stmt->bindParam(':title', $title, PDO::PARAM_STR);
      $stmt->bindParam(':body', $body, PDO::PARAM_STR);
      $stmt->execute();
    }
  }

  public function delete(){
    if ($this->article->getId()){
      $id = $this->article->getId();
      $stmt = $this->dbh->prepare("UPDATE articles SET is_delete=1 WHERE id=:id");
      $stmt->bindParam(':id', $id, PDO::PARAM_INT);
      $stmt->execute();
    }
  }

  public function find($id){
    $stmt = $this->dbh->prepare("SELECT * FROM articles WHERE id=:id AND is_delete=0");
    $stmt->bindParam(':id', $id, PDO::PARAM_INT);
    $stmt->execute();
    $articles = $this->getArticles($stmt->fetchAll(PDO::FETCH_ASSOC));
    return $articles[0];
  }

  public function findAll(){
    $stmt = $this->dbh->prepare("SELECT * FROM articles WHERE is_delete=0 ORDER BY created_at DESC");
    $stmt->execute();
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $articles = $this->getArticles($results);
    return $articles;
  }

  public function getPager($page = 1, $limit = 10){
    $start = ($page - 1) * $limit;  // LIMIT x, y：1ページ目を表示するとき、$startは0になる
    // 2ページ目を作成したい→先頭からの距離が10（オフセット10、インデックス番号10）から数えて10個の記事がを抽出
    // totalが総記事数で、articlesが実際に表示される一部の記事内容
    $pager = array('total' => null, 'articles' => null);

    // 総記事数
    // ページングのリンクを何ページ分表示するか決めるために必要
    // カウントされた行数のみが返ってくる
    $stmt = $this->dbh->prepare("SELECT COUNT(*) FROM articles WHERE is_delete=0");
    $stmt->execute();
    $pager['total'] = $stmt->fetchColumn();

    // 表示する一部のデータ
    $stmt = $this->dbh->prepare("SELECT * FROM articles
      WHERE is_delete=0
      ORDER BY created_at DESC
      LIMIT :start, :limit");
    $stmt->bindParam(':start', $start, PDO::PARAM_INT);
    $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();
    $pager['articles'] = $this->getArticles($stmt->fetchAll(PDO::FETCH_ASSOC));
    return $pager;
  }

  private function getArticles($results){
    $articles = [];
    foreach ($results as $result){
      $article = new Article();
      $article->setId($result['id']);
      $article->setTitle($result['title']);
      $article->setBody($result['body']);
      // $article->setFilename($result['filename']);
      $article->setCreatedAt($result['created_at']);
      $article->setUpdatedAt($result['updated_at']);
      $articles[] = $article;
    }
    return $articles;
  }
}
