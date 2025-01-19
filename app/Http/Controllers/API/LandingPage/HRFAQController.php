<?php

namespace App\Http\Controllers\API\LandingPage;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\FAQ;
use App\Models\HRRule;
use Maatwebsite\Excel\Facades\Excel;

class HRFAQController extends Controller
{
    public function landingPage()
    {
        $hrRules = HRRule::whereNull('deleted_at')->get();
        $faqs = FAQ::whereNull('deleted_at')->get();

        return response()->json([
            'hr_rules' => $hrRules,
            'faqs' => $faqs
        ]);
    }

    public function storeOrUpdateFAQ(Request $request)
    {
        return $request->hasFile('file') 
            ? $this->handleFAQFileUpload($request) 
            : $this->handleManualFAQInsert($request);
    }

    public function storeOrUpdateHRRule(Request $request)
    {
        return $request->hasFile('file') 
            ? $this->handleHRRuleFileUpload($request) 
            : $this->handleManualHRRuleInsert($request);
    }

    public function softDeleteFAQ($id)
    {
        $faq = FAQ::find($id);

        if (!$faq) {
            return response()->json(['message' => 'FAQ not found'], 404);
        }

        $faq->delete();

        return response()->json(['message' => 'FAQ soft-deleted successfully']);
    }

    public function softDeleteHRRule($id)
    {
        $hrRule = HRRule::find($id);

        if (!$hrRule) {
            return response()->json(['message' => 'HR Rule not found'], 404);
        }

        $hrRule->delete();

        return response()->json(['message' => 'HR Rule soft-deleted successfully']);
    }

    public function updateFAQ(Request $request, $id)
    {
        $faq = FAQ::find($id);

        if (!$faq) {
            return response()->json(['message' => 'FAQ not found'], 404);
        }

        $validatedData = $request->validate([
            'question' => 'required|string|max:255',
            'answer' => 'required|string',
        ]);

        $faq->fill($validatedData)->save();

        return response()->json([
            'message' => 'FAQ updated successfully',
            'faq' => $faq
        ]);
    }

    public function updateHRRule(Request $request, $id)
    {
        $hrRule = HRRule::find($id);

        if (!$hrRule) {
            return response()->json(['message' => 'HR Rule not found'], 404);
        }

        $validatedData = $request->validate([
            'rule' => 'required|string|max:255',
        ]);

        $hrRule->fill($validatedData)->save();

        return response()->json([
            'message' => 'HR Rule updated successfully',
            'hr_rule' => $hrRule
        ]);
    }

    private function handleFAQFileUpload(Request $request)
    {
        $request->validate(['file' => 'required|file|mimes:csv,xlsx,xls']);
        $faqs = $this->parseFile($request->file('file'));

        $savedFAQs = array_filter(array_map(function ($faqData) {
            if (!empty($faqData['question']) && !empty($faqData['answer'])) {
                $faq = FAQ::firstOrNew(['question' => $faqData['question']]);
                $faq->answer = $faqData['answer'];
                $faq->save();
                return $faq;
            }
            return null;
        }, $faqs));

        return response()->json([
            'message' => 'FAQs uploaded and saved successfully',
            'faqs' => array_values($savedFAQs)
        ]);
    }

    private function handleManualFAQInsert(Request $request)
    {
        $validatedData = $request->validate([
            'faqs' => 'required|array',
            'faqs.*.question' => 'required|string|max:255',
            'faqs.*.answer' => 'required|string',
        ]);

        $savedFAQs = array_map(function ($faqData) {
            $faq = FAQ::firstOrNew(['question' => $faqData['question']]);
            $faq->fill($faqData)->save();
            return $faq;
        }, $validatedData['faqs']);

        return response()->json([
            'message' => 'FAQs saved successfully',
            'faqs' => $savedFAQs
        ]);
    }

    private function handleHRRuleFileUpload(Request $request)
    {
        $request->validate(['file' => 'required|file|mimes:csv,xlsx,xls']);
        $hrRules = $this->parseFile($request->file('file'));

        $savedHRRules = array_filter(array_map(function ($ruleData) {
            if (!empty($ruleData['rule'])) {
                $hrRule = HRRule::firstOrNew(['rule' => $ruleData['rule']]);
                $hrRule->save();
                return $hrRule;
            }
            return null;
        }, $hrRules));

        return response()->json([
            'message' => 'HR Rules uploaded and saved successfully',
            'hr_rules' => array_values($savedHRRules)
        ]);
    }

    private function handleManualHRRuleInsert(Request $request)
    {
        $validatedData = $request->validate([
            'rules' => 'required|array',
            'rules.*.rule' => 'required|string|max:255',
        ]);

        $savedHRRules = array_map(function ($ruleData) {
            $hrRule = HRRule::firstOrNew(['rule' => $ruleData['rule']]);
            $hrRule->save();
            return $hrRule;
        }, $validatedData['rules']);

        return response()->json([
            'message' => 'HR Rules saved successfully',
            'hr_rules' => $savedHRRules
        ]);
    }

    private function parseFile($file)
    {
        return Excel::toArray([], $file->getRealPath())[0] ?? []; // Reads the first sheet into an array
    }
}
