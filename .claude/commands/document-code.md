# Command: document-code

Check comment documentation for the files specified in $ARGUMENTS

## Steps
1. Read the files at path: $ARGUMENTS
2. Create or update comment the file header to describe the purpose of the file
3. For all public function
   1. Check if there is a comment header and create it if missing
   2. Check that all parameters are described as well as the return
   3. Check that the purpose of the function is correctly described.
4. Add comments for all non trivial section of the code. Explain the why, not the how that should be obvious from the code.
5. When parts of the code are difficult to understand, suggest refactoring, create a markdown document into doc/reviews with the suggestion and where they should be applied. Keep a todo list in this suggestions document to track refactoring.

 