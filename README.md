# WordCountSearch

Adds the ability to-do word-count range searches, sort by word count in search. 
Forum listings can be sorted and filter by word-count.

Works with the following Content Types:
- Posts
- Threads/Threadmarks - supports Threadmark for injecting wordcount for threadmark category 1 into xf_thread and filtering by it

## Compatibility notes:
Does NOT work with MySQL search, requires XF's enhanced search (Elasticsearch). Requires https://github.com/Xon/XenForo-SearchImprovements
Does not store wordcounts < 20 to the xf_post_word table, when using elastic search.