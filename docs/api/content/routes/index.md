---
title: "Routes API showcase-recruiter-webapp-saas"
description: "Routes exposees par showcase-recruiter-webapp-saas API 0.1.0."
---


# Routes API

Source OpenAPI : `http://localhost:18082/v1/docs.json`

| Methode | Route | Tag | Resume |
| --- | --- | --- | --- |
| GET | [`/v1/candidates/{id}`](/routes/candidate#get-v1-candidates-id) | Candidate | Retrieves a Candidate resource. |
| GET | [`/v1/search/candidates`](/routes/candidatesearch#get-v1-search-candidates) | CandidateSearch | Retrieves a CandidateSearch resource. |
| GET | [`/v1/category-codes`](/routes/categorycode#get-v1-category-codes) | CategoryCode | Retrieves a CategoryCode resource. |
| GET | [`/v1/config`](/routes/config#get-v1-config) | Config | Retrieves a Config resource. |
| GET | [`/v1/organizations/{organizationId}/credit-transactions`](/routes/credittransaction#get-v1-organizations-organizationid-credit-transactions) | CreditTransaction | Retrieves a CreditTransaction resource. |
| GET | [`/v1/organizations/{organizationId}/credit-wallet`](/routes/creditwallet#get-v1-organizations-organizationid-credit-wallet) | CreditWallet | Retrieves a CreditWallet resource. |
| GET | [`/v1/organizations/{organizationId}/favorites`](/routes/favorite#get-v1-organizations-organizationid-favorites) | Favorite | Retrieves a Favorite resource. |
| POST | [`/v1/organizations/{organizationId}/favorites`](/routes/favorite#post-v1-organizations-organizationid-favorites) | Favorite | Creates a Favorite resource. |
| DELETE | [`/v1/organizations/{organizationId}/favorites/{candidateId}`](/routes/favorite#delete-v1-organizations-organizationid-favorites-candidateid) | Favorite | Removes the Favorite resource. |
| GET | [`/v1/languages`](/routes/language#get-v1-languages) | Language | Retrieves a Language resource. |
| POST | [`/v1/auth/login`](/routes/login-check#post-v1-auth-login) | Login Check | Creates a user token. |
| GET | [`/v1/me`](/routes/me#get-v1-me) | Me | Retrieves a Me resource. |
| POST | [`/v1/organizations`](/routes/organization#post-v1-organizations) | Organization | Creates a Organization resource. |
| GET | [`/v1/organizations/{id}`](/routes/organization#get-v1-organizations-id) | Organization | Retrieves a Organization resource. |
| PUT | [`/v1/organizations/{id}`](/routes/organization#put-v1-organizations-id) | Organization | Replaces the Organization resource. |
| GET | [`/v1/organizations/{id}/members`](/routes/organization#get-v1-organizations-id-members) | Organization | Retrieves a Organization resource. |
| GET | [`/v1/organizations/{organizationId}/revealed-profiles`](/routes/revealedprofile#get-v1-organizations-organizationid-revealed-profiles) | RevealedProfile | Retrieves a RevealedProfile resource. |
| POST | [`/v1/organizations/{organizationId}/revealed-profiles`](/routes/revealedprofile#post-v1-organizations-organizationid-revealed-profiles) | RevealedProfile | Creates a RevealedProfile resource. |
| GET | [`/v1/star-profile-cards`](/routes/starprofilecard#get-v1-star-profile-cards) | StarProfileCard | Retrieves a StarProfileCard resource. |
