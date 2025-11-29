# Query Examples for News Manager GraphQL

# 1. Get News by ID with Categories

query GetNewsById {
newsById(id: 1) {
news_id
news_title
news_content
news_status
created_at
categories {
category_id
category_name
category_description
}
}
}

# 2. Get News List with Pagination and Filters

query GetNewsList {
newsList(
pageSize: 10,
currentPage: 1,
filter: {
news_status: { eq: "1" }
news_title: { like: "technology" }
}
) {
items {
news_id
news_title
news_content
news_status
categories {
category_id
category_name
}
}
page_info {
page_size
current_page
total_pages
}
total_count
}
}

# 3. Get Category by ID with Children and News

query GetCategoryById {
categoryById(id: 1) {
category_id
category_name
category_description
category_status
parent_ids
children {
category_id
category_name
category_status
}
news {
news_id
news_title
news_status
}
}
}

# 4. Get Categories by Parent

query GetCategoriesByParent {
categoriesByParent(parentId: 1) {
category_id
category_name
category_description
category_status
}
}

# 5. Get News List by Category

query GetNewsListByCategory {
newsListByCategory(categoryId: 1) {
news_id
news_title
news_content
news_status
created_at
}
}

# 6. Create News

mutation CreateNews {
createNews(
input: {
news_title: "New Technology Article"
news_content: "This is the content of the new technology article..."
news_status: 1
category_ids: [1, 2]
}
) {
news_id
news_title
news_content
news_status
created_at
categories {
category_id
category_name
}
}
}

# 7. Update News

mutation UpdateNews {
updateNews(
id: 1,
input: {
news_title: "Updated Technology Article"
news_content: "This is the updated content..."
news_status: 1
category_ids: [1, 3]
}
) {
news_id
news_title
news_content
updated_at
categories {
category_id
category_name
}
}
}

# 8. Delete News

mutation DeleteNews {
deleteNews(id: 1)
}

# 9. Create Category

mutation CreateCategory {
createCategory(
input: {
category_name: "Technology"
category_description: "Technology related news"
category_status: 1
parent_ids: [1]
}
) {
category_id
category_name
category_description
category_status
parent_ids
created_at
}
}

# 10. Update Category

mutation UpdateCategory {
updateCategory(
id: 1,
input: {
category_name: "Updated Technology"
category_description: "Updated description for technology category"
category_status: 1
}
) {
category_id
category_name
category_description
updated_at
}
}

# 11. Delete Category

mutation DeleteCategory {
deleteCategory(id: 1)
}

# 12. Add News to Category

mutation AddNewsToCategory {
addNewsToCategory(newsId: 1, categoryId: 2)
}

# 13. Remove News from Category

mutation RemoveNewsFromCategory {
removeNewsFromCategory(newsId: 1, categoryId: 2)
}

# 14. Complex Query - Get All Categories with Hierarchy

query GetCategoryHierarchy {
categoryList(
pageSize: 100,
filter: {
category_status: { eq: "1" }
}
) {
items {
category_id
category_name
category_description
parent_ids
children {
category_id
category_name
children {
category_id
category_name
}
}
parents {
category_id
category_name
}
news {
news_id
news_title
news_status
}
}
total_count
}
}
