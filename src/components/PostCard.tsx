// ...existing code...
export default function PostCard({ post }: PostCardProps) {
  const [showComments, setShowComments] = useState(false);
  const [showCommentInput, setShowCommentInput] = useState(false);
  // ...existing code...

  return (
    <Card className="mb-4">
      {/* ...existing code... */}

      <CardFooter className="pt-4">
        <div className="flex items-center justify-between w-full mb-4">
          {/* ...existing code... */}
        </div>

        {!showCommentInput ? (
          <Button
            variant="ghost"
            size="sm"
            onClick={() => setShowCommentInput(true)}
            className="mb-2"
          >
            💬 Agregar comentario
          </Button>
        ) : (
          <div className="space-y-2 mb-4">
            <form onSubmit={handleCommentSubmit} className="flex gap-2">
              {/* ...existing code... */}
            </form>
            <Button
              variant="ghost"
              size="sm"
              onClick={() => setShowCommentInput(false)}
            >
              Cancelar
            </Button>
          </div>
        )}

        {/* ...existing code... */}
      </CardFooter>
    </Card>
  );
}

