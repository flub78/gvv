# Command: document-code

Check comment documentation for the files specified in $ARGUMENTS

## Steps
1. Read the files at path: $ARGUMENTS
2. Create or update comment the file header to describe the purpose of the file
3. For all public function
   1. Check if there is a comment header and create it if missing
   2. Check that all parameters are described as well as the return
   3. Check that the purpose of the function is correctly described.
4. Do not paraphrase the code. Do not add comments to explain variables when their name is self explanatory.
5. update the documentation_assessment_2025.md to track progress
6. Follow these principles:
   - File headers: 1-2 line purpose, no extended explanations
   - Functions: Brief description + @param/@return tags only
   - Inline comments: Only where code isn't self-explanatory
   - No examples: Code should be clear enough without them
   - No "why" explanations: Trust developers to understand context


 